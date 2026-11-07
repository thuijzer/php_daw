<?php

namespace Thuijzer\PhpDaw\Processors\Generators;

use SplFixedArray;
use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Enums\ADSR;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Enums\SignalMessage;
use Thuijzer\PhpDaw\Enums\WaveForm;
use Thuijzer\PhpDaw\Helpers\NoteHelper;
use Thuijzer\PhpDaw\Helpers\SynthHelper;
use Thuijzer\PhpDaw\Processors\IProcessor;

class SimpleTone implements IProcessor
{
    public function __construct(
        readonly private WaveForm $waveForm,
        readonly private float $attack = 0.01,
        readonly private float $decay = 8,
        readonly private float $sustain = 0.0,
        readonly private float $release = 0.03,
        readonly private float $stereoDelayMs = 0.1
    )
    {
    }

    public function getName(): string
    {
        return substr(strrchr(self::class, '\\'), 1);
    }

    /**
     * @param SampleRate $sampleRate
     * @param int $sampleOffset
     * @param SplFixedArray $input channels and samples for each channel
     * @param Signal[] $signals
     * @return void
     */
    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        SplFixedArray &$input,
        array $signals
    ): void {
        $polyphony = 6;

        /** @var SimpleTonePoly[] $poly */
        $poly = [];
        for ($i = 0; $i < $polyphony; $i++) {
            $poly[$i] = new SimpleTonePoly(
                $this->waveForm,
                $sampleRate,
                $this->attack,
                $this->decay,
                $this->sustain,
                $this->release,
                $this->stereoDelayMs
            );
        }

        $totalChannels = count($input);
        $totalSamples = count($input[0]);
        $totalSignals = count($signals);
        $signalIndex = 0;

        for ($s = 0; $s < $totalSamples; $s++) {
            $sample = $s + $sampleOffset;
            while ($signalIndex < $totalSignals && $signals[$signalIndex]->sample === $sample) {
                $currentSignal = $signals[$signalIndex];

                if ($currentSignal->message == SignalMessage::NoteOn) {
                    for($p = 0; $p < $polyphony; $p++) {
                        if($poly[$p]->getState() === ADSR::Idle) {
                            $poly[$p]->on($currentSignal->value1, $currentSignal->value2 / 127.0);
                            break;
                        }
                    }
                }

                if ($currentSignal->message == SignalMessage::NoteOff) {
                    for($p = 0; $p < $polyphony; $p++) {
                        if($poly[$p]->getState() !== ADSR::Idle && $poly[$p]->getNote() == $currentSignal->value1) {
                            $poly[$p]->off();
                        }
                    }
                }

                $signalIndex++;
            }

            $leftAmp = $input[0][$s];
            $rightAmp = $totalChannels > 1 ? $input[1][$s] : 0.0;

            $totalPolyAmpL = 0;
            $totalPolyAmpR = 0;
            for($p = 0; $p < count($poly); $p++) {
                if($poly[$p]->getState() !== ADSR::Idle) {
                    $poly[$p]->synthesize();
                    $totalPolyAmpL += $poly[$p]->getAmpL();
                    $totalPolyAmpR += $poly[$p]->getAmpR();
                }
            }

            $totalPolyAmpL /= $polyphony;
            $totalPolyAmpR /= $polyphony;

            $leftAmp += $totalPolyAmpL;
            $input[0][$s] = $leftAmp;
            if ($totalChannels > 1) {
                $rightAmp += $totalPolyAmpR;
                $input[1][$s] = $rightAmp;
            }
        }
    }
}

class SimpleTonePoly extends SynthHelper
{
    private float $frequency = 0.0;
    private float $phaseL = 0.0;
    private float $phaseR = 0.0;
    private float $attackVelocity = 0.0;
    private int $note;
    private float $ampL;
    private float $ampR;

    public function __construct(
        private readonly WaveForm $waveForm,
        protected SampleRate $sampleRate,
        protected float $attack,
        protected float $decay,
        protected float $sustain,
        protected float $release,
        protected float $stereoDelayMs = 0.1
    ) {
        parent::__construct(
            $sampleRate,
            $attack,
            $decay,
            $sustain,
            $release
        );
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function getAmpL(): float
    {
        return $this->ampL;
    }

    public function getAmpR(): float
    {
        return $this->ampR;
    }

    public function on(int $note, float $velocity):void
    {
        $this->note = $note;
        $this->state = ADSR::Attack;
        $this->frequency = NoteHelper::frequencyFromNote($note);
        $this->amplitude = 0.0;
        $this->attackVelocity = $velocity;
        $this->phaseL = 0.0;
        $this->phaseR = $this->stereoDelayMs / $this->sampleRate->value;
        $this->amplitudeAtRelease = 0.0;
        $this->ampL = 0.0;
        $this->ampR = 0.0;
    }

    public function off(): void
    {
        $this->state = ADSR::Release;
        $this->amplitudeAtRelease = $this->amplitude;
    }

    public function synthesize():void
    {
        if($this->state === ADSR::Idle) {
            return;
        }

        $this->calcAmplitude();

        $gain = $this->amplitude * $this->attackVelocity;
        $nyquist = $this->sampleRate->value * 0.5;
        $fadeStart = $nyquist * 0.8;
        if($this->frequency >= $fadeStart) {
            $t = ($nyquist - $this->frequency) / ($nyquist - $fadeStart);
            $t = min(1, max(0, $t));
            $gain *= $t;
        }

        switch ($this->waveForm) {
            case WaveForm::Square:
                $this->ampL = $gain * ($this->phaseL < 0.5 ? 1 : -1);
                $this->ampR = $gain * ($this->phaseR < 0.5 ? 1 : -1);
                break;
            case WaveForm::Triangle:
                $this->ampL = $gain * (4 * abs(fmod($this->phaseL, 1) - 0.5) - 1);
                $this->ampR = $gain * (4 * abs(fmod($this->phaseR, 1) - 0.5) - 1);
                break;
            case WaveForm::Sawtooth:
                $this->ampL = $gain * (2 * fmod($this->phaseL, 1) - 1);
                $this->ampR = $gain * (2 * fmod($this->phaseR, 1) - 1);
                break;
            case WaveForm::Noise:
                $this->ampL = $gain * rand(-1.0, 1.0);
                $this->ampR = $this->ampL;
                break;
            case WaveForm::Sine:
            default:
                $this->ampL = $gain * sin($this->phaseL * 2 * M_PI);
                $this->ampR = $gain * sin($this->phaseR * 2 * M_PI);
                break;
        }

        $this->phaseL += $this->frequency / $this->sampleRate->value;
        if ($this->phaseL >= 1){
            $this->phaseL -= 1;
        }
        $this->phaseR += $this->frequency / $this->sampleRate->value;
        if ($this->phaseR >= 1){
            $this->phaseR -= 1;
        }
    }
}