<?php

namespace Thuijzer\PhpDaw\Helpers;

use Thuijzer\PhpDaw\Enums\ADSR;
use Thuijzer\PhpDaw\Enums\SampleRate;

class SynthHelper
{
    protected float $amplitude = 0.0;
    protected ADSR $state = ADSR::Idle;
    protected float $amplitudeAtRelease = 0.0;

    public function __construct(
        protected SampleRate $sampleRate,
        protected float $attack,
        protected float $decay,
        protected float $sustain,
        protected float $release
    ) {
    }

    public function getState(): ADSR
    {
        return $this->state;
    }

    public function calcAmplitude(): void
    {
        switch ($this->state) {
            case ADSR::Attack:
                if ($this->attack <= 0) {
                    $this->amplitude = 1;
                    $this->state = ADSR::Decay;
                } else {
                    $this->amplitude += 1 / ($this->attack * $this->sampleRate->value);
                    if ($this->amplitude >= 1) {
                        $this->amplitude = 1;
                        $this->state = ADSR::Decay;
                    }
                }
                break;
            case ADSR::Decay:
                if ($this->decay <= 0) {
                    $this->amplitude = $this->sustain;
                    $this->state = ADSR::Sustain;
                } else {
                    $this->amplitude -= (1 - $this->sustain) / ($this->decay * $this->sampleRate->value);
                    if ($this->amplitude <= $this->sustain) {
                        $this->amplitude = $this->sustain;
                        $this->state = ADSR::Sustain;
                    }
                }
                break;
            case ADSR::Sustain:
                $this->amplitude = $this->sustain;
                break;
            case ADSR::Release:
                if ($this->release <= 0) {
                    $this->amplitude = 0;
                    $this->state = ADSR::Idle;
                } else {
                    $this->amplitude -= $this->amplitudeAtRelease / ($this->release * $this->sampleRate->value);
                    if ($this->amplitude < 0) {
                        $this->amplitude = 0;
                        $this->state = ADSR::Idle;
                    }
                }
                break;
            default:
                $this->amplitude = 0;
                $this->state = ADSR::Idle;
                break;
        }

        if ($this->amplitude < 0.00001) {
            $this->amplitude = 0;
            $this->state = ADSR::Idle;
        }
    }
}