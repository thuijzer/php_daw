<?php

namespace Thuijzer\PhpDaw;

use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Data\Track;
use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\OutputFormat;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Processors\IProcessor;
use Thuijzer\PhpDaw\Renderers\Wav;

class Daw
{
    /** @var Track[] */
    private array $tracks = [];
    /** @var IProcessor[] */
    private array $mainProcessors = [];
    /** @var Signal[] */
    private array $mainSignals = [];

    public function __construct(
        readonly public SampleRate $sampleRate,
        readonly public BitDepth $bitDepth,
        readonly public OutputChannels $outputChannels
    ) {
    }

    public function addTrack(Track $track): void
    {
        $this->tracks[] = $track;
    }

    public function setMainProcessors(array $mainProcessors): void
    {
        $this->mainProcessors = $mainProcessors;
    }

    public function setMainSignals(array $mainSignals): void
    {
        $this->mainSignals = $mainSignals;
    }

    /**
     * @param OutputFormat $outputFormat
     * @param int $startSample
     * @param int $endSample
     * @param string|null $filename null to output to stdout
     * @return void
     */
    public function render(
        OutputFormat $outputFormat,
        int $startSample,
        int $endSample,
        ?string $filename = null
    ): void {
        if($endSample <= $startSample) {
            throw new \InvalidArgumentException('End sample must be greater than start sample');
        }

        $totalSamples = $endSample - $startSample;
        $samples = new \SplFixedArray($this->outputChannels->value);
        for($c = 0; $c < $this->outputChannels->value; $c++) {
            $samples[$c] = new \SplFixedArray($totalSamples);
            for ($s = 0; $s < $totalSamples; $s++) {
                $samples[$c][$s] = 0.0;
            }
        }

        foreach ($this->tracks as $track) {
            foreach($track->processors as $processor) {
                $processor->process(
                    $this->sampleRate,
                    $startSample,
                    $samples,
                    $track->getSignals()
                );
            }
        }

        foreach ($this->mainProcessors as $processor) {
            $processor->process(
                $this->sampleRate,
                $startSample,
                $samples,
                $this->mainSignals
            );
        }

        switch ($outputFormat) {
            case OutputFormat::WAV:
                Wav::render(
                    $this->sampleRate,
                    $this->bitDepth,
                    $this->outputChannels,
                    $samples,
                    $filename
                );
                break;
        }
    }
}