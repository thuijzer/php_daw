<?php

namespace Thuijzer\PhpDaw\Processors\Effects;

use SplFixedArray;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Processors\IProcessor;

class Fade implements IProcessor
{
    public function __construct(
        readonly private FadeType $type,
        readonly private int $startSample,
        readonly private int $endSample
    ) {
    }

    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        SplFixedArray &$input,
        array $signals
    ): void {
        $isOut = $this->type === FadeType::Out;

        $numSamples = $this->endSample - $this->startSample;
        $minDb = -60.0;

        $sampleIndex = 0;
        for ($s = $this->startSample; $s < $this->endSample; $s++, $sampleIndex++) {
            $progress = ($numSamples > 1) ? ($sampleIndex / ($numSamples - 1)) : 1.0;

            if ($isOut) {
                $db = $minDb * $progress;
                $multiplier = ($sampleIndex === $numSamples - 1) ? 0.0 : pow(10, $db / 20);
            } else {
                $db = $minDb * (1.0 - $progress);
                $multiplier = ($sampleIndex === 0) ? 0.0 : pow(10, $db / 20);
            }

            for ($c = 0; $c < count($input); $c++) {
                $input[$c][$s] *= $multiplier;
            }
        }
    }
}

enum FadeType
{
    case In;
    case Out;
}