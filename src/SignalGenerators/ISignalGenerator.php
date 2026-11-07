<?php

namespace Thuijzer\PhpDaw\SignalGenerators;

use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Enums\SampleRate;

interface ISignalGenerator
{
    /**
     * @return Signal[]
     */
    public function generate(): array;
}