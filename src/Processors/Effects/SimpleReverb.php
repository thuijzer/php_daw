<?php

namespace Thuijzer\PhpDaw\Processors\Effects;

use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Processors\IProcessor;

class SimpleReverb implements IProcessor
{
    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        \SplFixedArray &$input,
        array $signals
    ): void
    {
        // TODO
    }
}