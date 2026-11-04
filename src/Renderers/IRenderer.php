<?php

namespace Thuijzer\PhpDaw\Renderers;

use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\SampleRate;

interface IRenderer
{
    /**
     * @param SampleRate $sampleRate
     * @param BitDepth $bitDepth
     * @param OutputChannels $outputChannels
     * @param float[][] $samples
     * @param string|null $filename
     * @return void
     */
    public static function render(
        SampleRate $sampleRate,
        BitDepth $bitDepth,
        OutputChannels $outputChannels,
        array $samples,
        ?string $filename = null
    ): void;
}