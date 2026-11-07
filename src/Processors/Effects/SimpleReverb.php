<?php

namespace Thuijzer\PhpDaw\Processors\Effects;

use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Helpers\SchroederAllPassFilter;
use Thuijzer\PhpDaw\Processors\IProcessor;

class SimpleReverb implements IProcessor
{
    private readonly array $times;

    public function __construct(
        SimpleReverbType $type,
        private readonly float $mix,
        private readonly float $feedback,
        private readonly float $filterHigh,
        private readonly float $preDelayMs = 50
    ) {
        switch ($type) {
            case SimpleReverbType::Cathedral:
            default:
                $this->times = [0.0353, 0.0471, 0.0599, 0.0713, 0.0897, 0.1031, 0.1133, 0.1277];
                break;
        }
    }

    public function process(
        SampleRate $sampleRate,
        int $sampleOffset,
        \SplFixedArray &$input,
        array $signals
    ): void {
        $diffusers = [
            new SchroederAllPassFilter($sampleRate, 13.7, 0.7),
            new SchroederAllPassFilter($sampleRate, 19.1, 0.7),
            new SchroederAllPassFilter($sampleRate, 23.3, 0.7)
        ];

        $delays = array_map(fn($t) => array_fill(0, floor($t * $sampleRate->value), 0.0), $this->times);
        $pointers = array_fill(0, 8, 0.0);
        $lpLast = array_fill(0, 8, 0.0);
        $lpAlpha = $this->filterHigh;
        $preDelaySamples = floor($this->preDelayMs / 1000 * $sampleRate->value);
        $preDelayBuffer = array_fill(0, $preDelaySamples, 0.0);
        $preDelayPtr = 0;
        $reverbGainDry = cos($this->mix * M_PI * 0.5);
        $reverbGainWet = sin($this->mix * M_PI * 0.5);

        for($s = 0; $s < count($input[0]); $s++) {
            for($c = 0; $c < count($input); $c++) {

                // Pre-delay

                // Get the "delayed" version of the input
                $delayedInput = $preDelayBuffer[$preDelayPtr];
                // Write the current input into the buffer
                $preDelayBuffer[$preDelayPtr] = $input[$c][$s];
                // Advance and wrap pointer
                $preDelayPtr = ($preDelayPtr + 1) % $preDelaySamples;

                // Diffuse
                foreach($diffusers as $diffuser) {
                    $sample = $diffuser->process($input[$c][$s]);
                }

                // Get from delays
                $samples = [];
                foreach($delays as $i => $delay) {
                    $samples[$i] = $delay[$pointers[$i]];
                }

                // Low-pass and gain
                for($i = 0; $i < 8; $i++) {
                    $filtered = $samples[$i] + $lpAlpha * ($lpLast[$i] - $samples[$i]);
                    $lpLast[$i] = $filtered;
                    $samples[$i] = $filtered * $this->feedback;
                }

                // matrix mix
                $mixed = $this->mix8x8($samples);

                // write back
                for($i = 0; $i < 8; $i++) {
                    $delays[$i][$pointers[$i]] = $delayedInput + $mixed[$i];
                    $pointers[$i] = ($pointers[$i] + 1) % count($delays[$i]);
                }

                // write the sample
                $input[$c][$s] =
                    $input[$c][$s] * $reverbGainDry
                    + (array_sum($mixed) / 8 + $sample) * $reverbGainWet;
            }
        }
    }


    private function mix8x8(array $out): array
    {
        $a = $out[0]; $b = $out[1]; $c = $out[2]; $d = $out[3];
        $e = $out[4]; $f = $out[5]; $g = $out[6]; $h = $out[7];

        // stage 1
        $a1 = $a + $e; $b1 = $b + $f; $c1 = $c + $g; $d1 = $d + $h;
        $e1 = $a - $e; $f1 = $b - $f; $g1 = $c - $g; $h1 = $d - $h;

        // stage 2
        $a2 = $a1 + $c1; $b2 = $b1 + $d1; $c2 = $a1 - $c1; $d2 = $b1 - $d1;
        $e2 = $e1 + $g1; $f2 = $f1 + $h1; $g2 = $e1 - $g1; $h2 = $f1 - $h1;

        // stage 3
        $s = 0.3535; // 1 / sqrt(8)
        return [
            ($a2 + $b2) * $s, ($a2 - $b2) * $s, ($c2 + $d2) * $s, ($c2 - $d2) * $s,
            ($e2 + $f2) * $s, ($e2 - $f2) * $s, ($g2 + $h2) * $s, ($g2 - $h2) * $s
        ];
    }
}

enum SimpleReverbType
{
    case Cathedral;
}
