<?php

namespace Thuijzer\PhpDaw\Data;

use Thuijzer\PhpDaw\Processors\IProcessor;

class Track
{
    /**
     * @param IProcessor[] $processors
     * @param Signal[] $signals
     */
    public function __construct(
        readonly public array $processors,
        private array $signals,
        readonly public int $volume = 127,
        readonly public int $pan = 64
    ) {
        usort($this->signals, fn(Signal $a, Signal $b) => $a->sample <=> $b->sample);
    }

    /**
     * @return Signal[]
     */
    public function getSignals(): array
    {
        return $this->signals;
    }
}