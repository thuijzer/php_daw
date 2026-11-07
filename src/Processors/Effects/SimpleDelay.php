<?php

namespace Thuijzer\PhpDaw\Processors\Effects;

use SplFixedArray;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Processors\IProcessor;

class SimpleDelay implements IProcessor
{
    public function __construct(
        private readonly float $delayMs = 1200,
        private readonly float $decayMs = 4000,
        private readonly float $mix = 0.2
    ) {
    }

    public function getName(): string
    {
        return substr(strrchr(self::class, '\\'), 1);
    }

    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        SplFixedArray &$input,
        array $signals
    ): void {
        $bufferSize = floor(($this->delayMs / 1000) * $sampleRate->value);
        if ($bufferSize < 1) {
            $bufferSize = 1;
        }

        $feedback = pow(0.001, $this->delayMs / $this->decayMs);
        $feedback = max(0.0, min(0.999, $feedback));

        for ($c = 0; $c < count($input); $c++) {
            $buffer = new SplFixedArray($bufferSize);
            for ($b = 0; $b < $bufferSize; $b++) {
                $buffer[$b] = 0.0;
            }
            $pointer = 0;
            for ($s = 0; $s < count($input[0]); $s++) {
                $output = $buffer[$pointer];
                $buffer[$pointer] = $input[$c][$s] + $output * $feedback;
                $pointer = ($pointer + 1) % $bufferSize;
                $input[$c][$s] += $output * $this->mix;
            }
        }
    }
}