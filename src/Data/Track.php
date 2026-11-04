<?php

namespace Thuijzer\PhpDaw\Data;

use Thuijzer\PhpDaw\Processors\IProcessor;

readonly class Track
{
    /**
     * @param IProcessor[] $processors
     * @param Signal[] $signals
     */
    public function __construct(
        public array $processors,
        public array $signals,
        public int $volume = 127,
        public int $pan = 64
    ) {
    }
}