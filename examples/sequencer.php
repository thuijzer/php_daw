<?php

use Thuijzer\PhpDaw\Data\Track;
use Thuijzer\PhpDaw\Daw;
use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\OutputFormat;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Enums\WaveForm;
use Thuijzer\PhpDaw\Processors\Effects\Fade;
use Thuijzer\PhpDaw\Processors\Effects\FadeType;
use Thuijzer\PhpDaw\Processors\Effects\Normalize;
use Thuijzer\PhpDaw\Processors\Effects\SimpleDelay;
use Thuijzer\PhpDaw\Processors\Effects\SimpleReverb;
use Thuijzer\PhpDaw\Processors\Effects\SimpleReverbType;
use Thuijzer\PhpDaw\Processors\Generators\SimpleTone;
use Thuijzer\PhpDaw\SignalGenerators\Sequencer;
use Thuijzer\PhpDaw\SignalGenerators\SequencerNote;

include __DIR__ . '/../vendor/autoload.php';

$sampleRate = SampleRate::R44100;

$daw = new Daw(
    $sampleRate,
    BitDepth::D16,
    OutputChannels::STEREO,
    true
);

$sequencer = new Sequencer(
    [
        new SequencerNote(36, 127),

        new SequencerNote(55, 127),
        new SequencerNote(60, 127),
        new SequencerNote(67, 100),
        new SequencerNote(52, 110),
        new SequencerNote(71, 90),
        new SequencerNote(64, 110),
    ],
    0.2 * $sampleRate->value,
    0.2 * $sampleRate->value,
    0,
    5 * $sampleRate->value
);

$track = new Track(
    [
        new SimpleTone(
            WaveForm::Triangle,
            0.01,
            5,
            0.0,
            0.1,
            100
        )
    ],
    $sequencer->generate()
);
$daw->addTrack($track);

$daw->setMainProcessors([
    new SimpleReverb(
        SimpleReverbType::Cathedral,
        0.6,
        0.8,
        0.1,
        50
    ),
    new SimpleDelay(
        300,
        5000,
        .3
    ),
    new Normalize(),
    new Fade(FadeType::In, 0, 0.2 * $sampleRate->value),
    new Fade(FadeType::Out, 3 * $sampleRate->value, 5 * $sampleRate->value),
]);

$daw->render(
    OutputFormat::WAV,
    0,
    5 * $sampleRate->value, // Render 4 seconds of audio
    __DIR__ . '/sequencer.wav'
);
