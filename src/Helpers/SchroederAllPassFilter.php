<?php

namespace Thuijzer\PhpDaw\Helpers;

use Thuijzer\PhpDaw\Enums\SampleRate;

class SchroederAllPassFilter
{
    private array $buffer;
    private int $pointer;

    public function __construct(
        private readonly SampleRate $sampleRate,
        private readonly float $delayMs,
        private readonly float $gain = 0.5
    ) {
        $samples = floor(($this->delayMs / 1000) * $this->sampleRate->value);
        $this->buffer = array_fill(0, $samples, 0.0);
        $this->pointer = 0;
    }

    public function process(int $sample)
    {
        $bufferOut = $this->buffer[$this->pointer];
        $inputFixed = $sample + ($bufferOut * $this->gain);
        $output = -$this->gain * $inputFixed + $bufferOut;

        $this->buffer[$this->pointer] = $inputFixed;
        $this->pointer = ($this->pointer + 1) % count($this->buffer);

        return $output;
    }
}