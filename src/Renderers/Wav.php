<?php

namespace Thuijzer\PhpDaw\Renderers;

use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\SampleRate;

class Wav implements IRenderer
{
    public static function render(
        SampleRate $sampleRate,
        BitDepth $bitDepth,
        OutputChannels $outputChannels,
        \SplFixedArray $samples,
        ?string $filename = null
    ): void {

        $sampleRate = $sampleRate->value;
        $numChannels = $outputChannels->value;
        $bitsPerSample = $bitDepth->value;
        $totalSamples = count($samples[0]);
        $pcmData = '';
        $sampleMultiplier = 0;

        switch ($bitDepth) {
            case BitDepth::D16:
                $sampleMultiplier = 32767;
                break;
        }

        for ($s = 0; $s < $totalSamples; $s++) {
            for($c = 0; $c < $numChannels; $c++) {
                $intValue = (int)(min(1, max(-1, $samples[$c][$s])) * $sampleMultiplier);
                $pcmData .= pack('s', $intValue);
            }
        }

        $dataSize = strlen($pcmData);
        $fileSize = 36 + $dataSize; // Total file size minus the first 8 bytes (RIFF + ChunkSize)

        $header = 'RIFF'; // ChunkID
        $header .= pack('V', $fileSize); // ChunkSize
        $header .= 'WAVE'; // Format
        $header .= 'fmt '; // Subchunk1ID
        $header .= pack('V', 16); // Subchunk1Size (16 for PCM)
        $header .= pack('v', 1); // AudioFormat (1 = PCM)
        $header .= pack('v', $numChannels); // NumChannels
        $header .= pack('V', $sampleRate); // SampleRate
        $header .= pack('V', ($sampleRate * $numChannels * $bitsPerSample) / 8); // ByteRate
        $header .= pack('v', ($numChannels * $bitsPerSample) / 8); // BlockAlign
        $header .= pack('v', $bitsPerSample); // BitsPerSample
        $header .= 'data'; // Subchunk2ID
        $header .= pack('V', $dataSize); // Subchunk2Size

        $wavFileContent = $header . $pcmData;

        if($filename) {
            file_put_contents($filename, $wavFileContent);
        } else {
            header('Content-Type: audio/wav');
            header('Content-Length: ' . strlen($wavFileContent));
            echo $wavFileContent;
            exit;
        }
    }
}