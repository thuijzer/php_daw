<?php

namespace Thuijzer\PhpDaw\Processors\Effects;

use SplFixedArray;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Processors\IProcessor;

class Normalize implements IProcessor
{
    public function __construct(
        readonly private bool $perChannel = false
    ) {

    }

    public function getName(): string
    {
        return substr(strrchr(self::class, '\\'), 1);
    }

    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        SplFixedArray &$input,
        array $signals
    ): void
    {
        $max = array_fill(0, count($input), 0.0);
        $multipliers = array_fill(0, count($input), 0.0);
        $totalMax = 0.0;
        $totalMultiplier = 0.0;

        for($c = 0; $c < count($input); $c++) {
            for($s = 0; $s < count($input[$c]); $s++) {
                $v = abs($input[$c][$s]);
                if($v > $max[$c]) {
                    $max[$c] = $v;
                    $multipliers[$c] = 1.0 / $max[$c];
                }
                if($v > $totalMax) {
                    $totalMax = $v;
                    $totalMultiplier = 1.0 / $totalMax;
                }
            }
        }

        for($c = 0; $c < count($input); $c++) {
            for($s = 0; $s < count($input[$c]); $s++) {
                $input[$c][$s] *= $this->perChannel ? $multipliers[$c] : $totalMultiplier;
            }
        }
    }
}