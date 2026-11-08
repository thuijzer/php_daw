<?php

use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Data\Track;
use Thuijzer\PhpDaw\Daw;
use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\OutputFormat;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Enums\SignalMessage;
use Thuijzer\PhpDaw\Enums\WaveForm;
use Thuijzer\PhpDaw\Helpers\TimeHelper;
use Thuijzer\PhpDaw\Processors\Effects\Fade;
use Thuijzer\PhpDaw\Processors\Effects\FadeType;
use Thuijzer\PhpDaw\Processors\Effects\Normalize;
use Thuijzer\PhpDaw\Processors\Effects\SimpleDelay;
use Thuijzer\PhpDaw\Processors\Generators\SimpleTone;
use Thuijzer\PhpDaw\SignalGenerators\Sequencer;
use Thuijzer\PhpDaw\SignalGenerators\SequencerNote;

include __DIR__ . '/../vendor/autoload.php';

$sampleRate = SampleRate::R44100;
$beat = TimeHelper::samplesFromBpm($sampleRate, 110);

$daw = new Daw(
    $sampleRate,
    BitDepth::D16,
    OutputChannels::STEREO,
    true
);

$sequencer1 = new Sequencer(
    [
        new SequencerNote(57, 127),
        new SequencerNote(60, 127),
        new SequencerNote(64, 127),
        new SequencerNote(67, 127),
    ],
    $beat / 4,
    $beat / 8,
    0,
    2 * $beat
);
$sequencer2 = new Sequencer(
    [
        new SequencerNote(53, 127),
        new SequencerNote(57, 127),
        new SequencerNote(60, 127),
        new SequencerNote(64, 127),
    ],
    $beat / 4,
    $beat / 8,
    2 * $beat,
    4 * $beat
);
$sequencer3 = new Sequencer(
    [
        new SequencerNote(52, 127),
        new SequencerNote(55, 127),
        new SequencerNote(59, 127),
        new SequencerNote(62, 127),
    ],
    $beat / 4,
    $beat / 8,
    4 * $beat,
    8 * $beat
);

$track = new Track(
    [
        new SimpleTone(
            WaveForm::Triangle,
            0.01,
            5,
            0.0,
            0.1,
            1000
        )
    ],
    array_merge(
        $sequencer1->generate(),
        $sequencer2->generate(),
        $sequencer3->generate()
    )
);
$daw->addTrack($track);

$track = new Track(
    [
        new SimpleTone(
            WaveForm::Sine,
            0.01,
            0.2,
            0.9,
            0.01,
            0
        )
    ],
    [
        new Signal(0, SignalMessage::NoteOn, 33, 80),
        new Signal(2 * $beat, SignalMessage::NoteOff, 33),
        new Signal(2 * $beat, SignalMessage::NoteOn, 36, 80),
        new Signal(4 * $beat, SignalMessage::NoteOff, 36),
        new Signal(4 * $beat, SignalMessage::NoteOn, 28, 80),
        new Signal(8 * $beat, SignalMessage::NoteOff, 28),
    ]
);
$daw->addTrack($track);

$daw->setMainProcessors([
    new SimpleDelay(
        300,
        5000,
        .3
    ),
    new Normalize(),
    new Fade(FadeType::In, 0, $beat / 16),
    new Fade(FadeType::Out, 8 * $beat, 9 * $beat),
]);

$daw->render(
    OutputFormat::WAV,
    0,
    9 * $beat, // Render 4 seconds of audio
    __DIR__ . '/sequencer.wav'
);
