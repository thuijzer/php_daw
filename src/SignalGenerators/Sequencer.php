<?php

namespace Thuijzer\PhpDaw\SignalGenerators;

use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Enums\SignalMessage;

class Sequencer implements ISignalGenerator
{
    /**
     * @param SequencerNote[] $notes
     * @param int $stepDurationSamples
     * @param int $noteDurationSamples
     * @param int $fromSample
     * @param int $toSample
     */
    public function __construct(
        readonly private array $notes,
        readonly private int $stepDurationSamples,
        readonly private int $noteDurationSamples,
        readonly private int $fromSample,
        readonly private int $toSample
    )
    {
    }

    /**
     * @return Signal[]
     */
    public function generate(): array
    {
        $signals = [];
        $onSteps = [];
        $n = 0;
        $i = 0;
        for($s = $this->fromSample; $s < $this->toSample; $s ++){
            if($n === 0) {
                $onSteps[] = [$i, $s];
                $signals[] = new Signal(
                    $s,
                    SignalMessage::NoteOn,
                    $this->notes[$i]->note,
                    $this->notes[$i]->velocity
                );
                $i ++;
                if($i >= count($this->notes)) {
                    $i = 0;
                }
            }

            $n ++;
            if($n > $this->stepDurationSamples) {
                $n = 0;
            }

            for($o = count($onSteps) - 1; $o >= 0; $o--) {
                if($s - $onSteps[$o][1] > $this->noteDurationSamples) {
                    $signals[] = new Signal(
                        $s,
                        SignalMessage::NoteOff,
                        $this->notes[$onSteps[$o][0]]->note
                    );
                    array_splice($onSteps, $o, 1);
                }
            }
        }

        return $signals;
    }
}

readonly class SequencerNote
{
    public function __construct(
        public int $note,
        public int $velocity
    ){
    }
}