<?php

namespace Thuijzer\PhpDaw\Processors;

use Thuijzer\PhpDaw\Enums\SampleRate;

class SimpleReverb implements IProcessor
{
    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        array &$input,
        array $signals
    ): void
    {
        // TODO
    }
}