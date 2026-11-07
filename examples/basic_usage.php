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
use Thuijzer\PhpDaw\Processors\Effects\Fade;
use Thuijzer\PhpDaw\Processors\Effects\FadeType;
use Thuijzer\PhpDaw\Processors\Effects\Normalize;
use Thuijzer\PhpDaw\Processors\Effects\SimpleDelay;
use Thuijzer\PhpDaw\Processors\Effects\SimpleReverb;
use Thuijzer\PhpDaw\Processors\Effects\SimpleReverbType;
use Thuijzer\PhpDaw\Processors\Generators\SimpleTone;

include __DIR__ . '/../vendor/autoload.php';

$sampleRate = SampleRate::R44100;

$daw = new Daw(
    $sampleRate,
    BitDepth::D16,
    OutputChannels::STEREO,
    true
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
    [
        new Signal(0 * $sampleRate->value, SignalMessage::NoteOn, 60, 127),
        new Signal(.75 * $sampleRate->value, SignalMessage::NoteOff, 60),

        new Signal(.5 * $sampleRate->value, SignalMessage::NoteOn, 64, 127),
        new Signal(1.25 * $sampleRate->value, SignalMessage::NoteOff, 64),

        new Signal(1 * $sampleRate->value, SignalMessage::NoteOn, 67, 127),
        new Signal(1.75 * $sampleRate->value, SignalMessage::NoteOff, 67),

        new Signal(2 * $sampleRate->value, SignalMessage::NoteOn, 60, 127),
        new Signal(3 * $sampleRate->value, SignalMessage::NoteOff, 60),
        new Signal(2 * $sampleRate->value, SignalMessage::NoteOn, 64, 127),
        new Signal(3 * $sampleRate->value, SignalMessage::NoteOff, 64),
        new Signal(2 * $sampleRate->value, SignalMessage::NoteOn, 67, 127),
        new Signal(3 * $sampleRate->value, SignalMessage::NoteOff, 67),
    ]
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
       1200,
        2000,
        .2
    ),
    new Normalize(),
    new Fade(FadeType::In, 0, 0.01 * $sampleRate->value),
    new Fade(FadeType::Out, 4 * $sampleRate->value, 5 * $sampleRate->value),
]);

$daw->render(
    OutputFormat::WAV,
    0,
    5 * $sampleRate->value, // Render 4 seconds of audio
    __DIR__ . '/basic_usage.wav'
);
