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
        array $samples,
        ?string $filename = null
    ): void {

        $sampleRate = $sampleRate->value;
        $numChannels = $outputChannels->value;
        $bitsPerSample = $bitDepth->value;

        $duration = 1; // seconds
        $totalSamples = $sampleRate * $duration;
        $pcmData = '';

        for ($i = 0; $i < $totalSamples; $i++) {
            // Generate sine wave value between -1 and 1
            $t = $i / $sampleRate;
            $sampleValue = sin(2 * M_PI * 440 * $t);

            // Scale to 16-bit signed integer range (-32768 to 32767)
            $intValue = (int)($sampleValue * 32767);

            // Pack into a 16-bit signed little-endian integer ('v' can be used with signed adjustment, or unpack/pack)
            // Using 'v' for unsigned short after shifting, or pack('s', ...) for signed 16-bit little-endian.
            $pcmData .= pack('s', $intValue);
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