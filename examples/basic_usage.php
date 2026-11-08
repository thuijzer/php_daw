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
use Thuijzer\PhpDaw\Helpers\NoteHelper;
use Thuijzer\PhpDaw\Helpers\TimeHelper;
use Thuijzer\PhpDaw\Processors\Effects\Fade;
use Thuijzer\PhpDaw\Processors\Effects\FadeType;
use Thuijzer\PhpDaw\Processors\Effects\Normalize;
use Thuijzer\PhpDaw\Processors\Effects\SimpleDelay;
use Thuijzer\PhpDaw\Processors\Effects\SimpleReverb;
use Thuijzer\PhpDaw\Processors\Effects\SimpleReverbType;
use Thuijzer\PhpDaw\Processors\Generators\SimpleTone;

include __DIR__ . '/../vendor/autoload.php';

$sampleRate = SampleRate::R44100;
$beat = TimeHelper::samplesFromBpm($sampleRate, 110);

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
        new Signal(0, SignalMessage::NoteOn, NoteHelper::mnfn('C4'), 127),
        new Signal($beat, SignalMessage::NoteOff, NoteHelper::mnfn('C4')),

        new Signal($beat, SignalMessage::NoteOn, NoteHelper::mnfn('E4'), 127),
        new Signal($beat * 2, SignalMessage::NoteOff, NoteHelper::mnfn('E4')),

        new Signal($beat * 2, SignalMessage::NoteOn, NoteHelper::mnfn('G4'), 127),
        new Signal($beat * 3, SignalMessage::NoteOff, NoteHelper::mnfn('G4')),

        new Signal($beat * 4, SignalMessage::NoteOn, NoteHelper::mnfn('C4'), 127),
        new Signal($beat * 6, SignalMessage::NoteOff, NoteHelper::mnfn('C4')),
        new Signal($beat * 4, SignalMessage::NoteOn, NoteHelper::mnfn('E4'), 127),
        new Signal($beat * 6, SignalMessage::NoteOff, NoteHelper::mnfn('E4')),
        new Signal($beat * 4, SignalMessage::NoteOn, NoteHelper::mnfn('G4'), 127),
        new Signal($beat * 6, SignalMessage::NoteOff, NoteHelper::mnfn('G4')),
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
    new Fade(FadeType::In, 0, $beat / 16),
    new Fade(FadeType::Out, $beat * 7, $beat * 8),
]);

$daw->render(
    OutputFormat::WAV,
    0,
    $beat * 8, // Render 4 seconds of audio
    __DIR__ . '/basic_usage.wav'
);
