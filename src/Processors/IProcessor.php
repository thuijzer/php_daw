<?php

namespace Thuijzer\PhpDaw\Processors;

use SplFixedArray;
use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Enums\SampleRate;

interface IProcessor
{
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
    ): void;
}