<?php

use Thuijzer\PhpDaw\Data\Signal;
use Thuijzer\PhpDaw\Data\Track;
use Thuijzer\PhpDaw\Daw;
use Thuijzer\PhpDaw\Enums\BitDepth;
use Thuijzer\PhpDaw\Enums\ControlChange;
use Thuijzer\PhpDaw\Enums\OutputChannels;
use Thuijzer\PhpDaw\Enums\OutputFormat;
use Thuijzer\PhpDaw\Enums\SampleRate;
use Thuijzer\PhpDaw\Enums\SignalMessage;
use Thuijzer\PhpDaw\Processors\SimpleReverb;
use Thuijzer\PhpDaw\Processors\SimpleTone;

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
        new Signal(0, SignalMessage::NoteOn, 60, 100),
        new Signal(3 * $sampleRate->value, SignalMessage::NoteOff, 60, 100),
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
