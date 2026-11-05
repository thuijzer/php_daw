<?php

namespace Thuijzer\PhpDaw\Processors\Generators;

use SplFixedArray;
use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Enums\ADSR;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Enums\SignalMessage;
use Thuijzer\PhpDaw\Helpers\NoteHelper;
use Thuijzer\PhpDaw\Helpers\SynthHelper;
use Thuijzer\PhpDaw\Processors\IProcessor;

class SimpleTone implements IProcessor
{
    private const float Attack = 0.01;
    private const float Decay = 8;
    private const float Sustain = 0.0;
    private const float Release = 0.03;

    public function __construct(
        readonly private SimpleToneWaveForm $waveForm
    )
    {

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
        $polyphony = 3;

        /** @var SimpleTonePoly[] $poly */
        $poly = [];
        for ($i = 0; $i < $polyphony; $i++) {
            $poly[$i] = new SimpleTonePoly(
                $this->waveForm,
                $sampleRate,
                self::Attack,
                self::Decay,
                self::Sustain,
                self::Release
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
                        if($poly[$p]->getIsActive() === false) {
                            $poly[$p]->on($currentSignal->value1, $currentSignal->value2 / 127.0);
                            break;
                        }
                    }
                }

                if ($currentSignal->message == SignalMessage::NoteOff) {
                    for($p = 0; $p < $polyphony; $p++) {
                        if($poly[$p]->getIsActive() === true && $poly[$p]->getNote() == $currentSignal->value1) {
                            $poly[$p]->off();
                        }
                    }
                }

                $signalIndex++;
            }

            $leftAmp = $input[0][$s];
            $rightAmp = $totalChannels > 1 ? $input[1][$s] : 0.0;

            $totalPolyAmp = 0;
            for($p = 0; $p < count($poly); $p++) {
                if($poly[$p]->getIsActive() === true) {
                    $poly[$p]->synthesize();
                    $totalPolyAmp += $poly[$p]->getAmp();
                }
            }

            $totalPolyAmp /= $polyphony;

            $leftAmp += $totalPolyAmp;
            $input[0][$s] = $leftAmp;
            if ($totalChannels > 1) {
                $rightAmp += $totalPolyAmp;
                $input[1][$s] = $rightAmp;
            }
        }
    }
}

class SimpleTonePoly extends SynthHelper
{
    private float $frequency = 0.0;
    private float $phase = 0.0;
    private float $attackVelocity = 0.0;
    private int $note;
    private float $amp;

    public function __construct(
        private SimpleToneWaveForm $waveForm,
        protected SampleRate $sampleRate,
        protected float $attack,
        protected float $decay,
        protected float $sustain,
        protected float $release
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

    public function getAmp(): float
    {
        return $this->amp;
    }

    public function on(int $note, float $velocity):void
    {
        $this->note = $note;
        $this->isActive = true;
        $this->state = ADSR::Attack;
        $this->frequency = NoteHelper::frequencyFromNote($note);
        $this->amplitude = 0.0;
        $this->attackVelocity = $velocity;
        $this->phase = 0.0;
        $this->amplitudeAtRelease = 0.0;
        $this->amp = 0.0;
    }

    public function off(): void
    {
        $this->state = ADSR::Release;
        $this->amplitudeAtRelease = $this->amplitude;
    }

    public function synthesize():void
    {
        if(!$this->isActive) {
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
            case SimpleToneWaveForm::Sine:
                $this->amp = $gain * sin($this->phase * 2 * M_PI);
                break;
            case SimpleToneWaveForm::Square:
                $this->amp = $gain * ($this->phase < 0.5 ? 1 : -1);
                break;
            case SimpleToneWaveForm::Triangle:
                $this->amp = $gain * (4 * abs(fmod($this->phase, 1) - 0.5) - 1);
                break;
        }

        $this->phase += $this->frequency / $this->sampleRate->value;
        if ($this->phase >= 1){
            $this->phase -= 1;
        }
    }
}

enum SimpleToneWaveForm {
    case Sine;
    case Square;
    case Triangle;
}