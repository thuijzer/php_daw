<?php

namespace Thuijzer\PhpDaw\Processors;

use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Enums\SampleRate;

interface IProcessor
{
    /**
     * @param SampleRate $sampleRate
     * @param int $sampleOffset
     * @param float[][] $input channels and samples for each channel
     * @param Signal[] $signals
     * @return void
     */
    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        array &$input,
        array $signals
    ): void;
}