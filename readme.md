# PHP DAW

A DAW for PHP that is able to generate audio files.

## Installation

run `composer require thuijzer/php_daw` to require the latest stable version of PHP DAW.

## Basic usage

```php
$sampleRate = SampleRate::R44100;

$daw = new Daw(
    $sampleRate,
    BitDepth::D16,
    OutputChannels::STEREO,
    true
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
    new SimpleReverb(
        SimpleReverbType::Cathedral,
        0.9,
        0.8,
        0.1,
        50
    )
]);

$daw->render(
    OutputFormat::WAV,
    0,
    4 * $sampleRate->value, // Render 4 seconds of audio
    __DIR__ . '/output.wav'
);
```

You can create multiple tracks that have a list of processors and a list of signals.

It is also possible to set main processors that are applied to all tracks.

Each processor is responsible for processing the signals and generating the audio data. If multiple processors are set, they are applied in the order they are set.

When all things are set, you can render the audio file to the standard output or to a file.

## Signal

Signals are almost compatible with MIDI messages. They have a message type and up to two values.
For Control Change messages, you can use the helper enum `ControlChange` to set `value1` of your message.
For example:
```php
new Signal(
    0,
    SignalMessage::ControlChange,
    ControlChange::Volume->value,
    127
)
// is the same as:
new Signal(
    0,
    SignalMessage::ControlChange,
    7,
    127
)
```

## IProcessor

To create a processor, you implement the `IProcessor` interface and add the processor to a track or a main processor list.

The `IProcessor` interface has a single method `process` that takes the following parameters:

- The `sampleRate` parameter represents the sample rate of the audio stream.
- The `sampleOffset` parameter represents the current sample offset in the audio stream. Samples before the offset are not present in the `samples` array.
- The `input` parameter is an array of input samples. The first level of the array represents the channels, and the second level represents the samples.
When the processor is the first in the list, all samples will be zero. 
The only exception is the first processor of the main processor list, which receives the audio data of all processed tracks.
Other processors receive the processed audio of the previous processor.
Sample values are floating point numbers in the range of -1.0 to 1.0.
- The `signals` parameter is an array of signal messages.
- The `process` method should modify the `samples` array in place and return nothing.

In a lot of cases you need to loop over the samples array and check if there are signals for the current sample.
An efficient way to do this is by using a cursor pattern. For example:
```php
$totalChannels = count($input);
$totalSamples = count($input[0]);
$totalSignals = count($signals);
$signalIndex = 0;

for($s = 0; $s < $totalSamples; $s++) {
    $sample = $s + $sampleOffset;
    while ($signalIndex < $totalSignals && $signals[$signalIndex]->sample === $sample) {
        $currentSignal = $signals[$signalIndex];
        
        // Do something
        
        $signalIndex++;
    }
    
    $leftAmp = $input[0][$s];
    $rightAmp = $totalChannels > 1 ? $input[1][$s] : 0.0;
    
    // Do something
    
    $input[0][$s] = $leftAmp;
    if($totalChannels > 1) {
        $input[1][$s] = $rightAmp;
    }
}
```

## Why!?

Because it's a fun exercise, a tool to learn about audio processing and a way to create cool music by using PHP.