<?php

use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Data\Track;
use Thuijzer\PhpDaw\Daw;
use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\OutputFormat;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Enums\SignalMessage;
use Thuijzer\PhpDaw\Processors\Effects\SimpleReverb;
use Thuijzer\PhpDaw\Processors\Generators\SimpleTone;

include __DIR__ . '/../vendor/autoload.php';

$sampleRate = SampleRate::R44100;

$daw = new Daw(
    $sampleRate,
    BitDepth::D16,
    OutputChannels::STEREO
);

$track = new Track(
    [
        new SimpleTone()
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
    new SimpleReverb()
]);

$daw->render(
    OutputFormat::WAV,
    0,
    4 * $sampleRate->value, // Render 4 seconds of audio
    __DIR__ . '/output.wav'
);
