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
    private int $renderStart = 0;

    public function __construct(
        readonly public SampleRate $sampleRate,
        readonly public BitDepth $bitDepth,
        readonly public OutputChannels $outputChannels,
        readonly public bool $debug = false
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
        if ($endSample <= $startSample) {
            throw new \InvalidArgumentException('End sample must be greater than start sample');
        }
        $this->renderStart = microtime(true);

        $this->log('Rendering from sample ' . $startSample . ' to ' . $endSample);

        $totalSamples = $endSample - $startSample;
        $samples = new \SplFixedArray($this->outputChannels->value);
        for ($c = 0; $c < $this->outputChannels->value; $c++) {
            $samples[$c] = new \SplFixedArray($totalSamples);
            for ($s = 0; $s < $totalSamples; $s++) {
                $samples[$c][$s] = 0.0;
            }
        }

        foreach ($this->tracks as $t => $track) {
            foreach ($track->processors as $p => $processor) {
                $this->log('Rendering track ' . ($t + 1) . ' processor ' . ($p + 1));
                $processor->process(
                    $this->sampleRate,
                    $startSample,
                    $samples,
                    $track->getSignals()
                );
            }
        }

        foreach ($this->mainProcessors as $p => $processor) {
            $this->log('Rendering main processor ' . ($p + 1));
            $processor->process(
                $this->sampleRate,
                $startSample,
                $samples,
                $this->mainSignals
            );
        }

        $this->log('Outputting');
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

        $this->log('Done');
        if($this->debug) {
            $this->log('Mem: ' . number_format(memory_get_usage() / 1024 / 1024, 2) . ' MB');
        }
    }

    private function log($message): void
    {
        if ($this->debug) {
            echo number_format(microtime(true) - $this->renderStart, 3) . ': ' . $message . PHP_EOL;
        }
    }
}