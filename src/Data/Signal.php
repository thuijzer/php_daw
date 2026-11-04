<?php

namespace Thuijzer\PhpDaw\Data;

use Thuijzer\PhpDaw\Enums\SignalMessage;

readonly class Signal
{
    public function __construct(
        public int $sample,
        public SignalMessage $message,
        public ?int $value1 = null,
        public ?int $value2 = null
    ) {
    }
}