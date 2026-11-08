<?php

namespace Thuijzer\PhpDaw\Helpers;

use Thuijzer\PhpDaw\Enums\SampleRate;

class TimeHelper
{
    public static function samplesFromBpm(SampleRate $sampleRate, float $bpm): int {
        return (int) (60 / $bpm * $sampleRate->value);
    }
}