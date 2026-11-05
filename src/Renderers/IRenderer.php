<?php

namespace Thuijzer\PhpDaw\Renderers;

use SplFixedArray;
use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\SampleRate;

interface IRenderer
{
    /**
     * @param SampleRate $sampleRate
     * @param BitDepth $bitDepth
     * @param OutputChannels $outputChannels
     * @param SplFixedArray $samples
     * @param string|null $filename
     * @return void
     */
    public static function render(
        SampleRate $sampleRate,
        BitDepth $bitDepth,
        OutputChannels $outputChannels,
        SplFixedArray $samples,
        ?string $filename = null
    ): void;
}