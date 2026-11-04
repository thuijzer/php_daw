<?php

namespace Thuijzer\PhpDaw\Processors;

use Thuijzer\PhpDaw\Enums\SampleRate;

class SimpleTone implements IProcessor
{
    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        array &$input,
        array $signals
    ): void
    {
        $totalChannels = count($input);
        $totalSamples = count($input[0]);
        $totalSignals = count($signals);
        $signalIndex = 0;

        for($s = 0; $s < $totalSamples; $s++) {
            $sample = $s + $sampleOffset;
            while ($signalIndex < $totalSignals && $signals[$signalIndex]->sample === $sample) {
                $currentSignal = $signals[$signalIndex];

                // Do something

                $signalIndex++;
            }

            $leftAmp = $input[0][$s];
            $rightAmp = $totalChannels > 1 ? $input[1][$s] : 0.0;

            // Do something

            $input[0][$s] = $leftAmp;
            if($totalChannels > 1) {
                $input[1][$s] = $rightAmp;
            }
        }
    }
}