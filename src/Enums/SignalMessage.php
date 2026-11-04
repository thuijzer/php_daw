<?php

namespace Thuijzer\PhpDaw\Enums;

enum SignalMessage
{
    case NoteOn;
    case Aftertouch;
    case NoteOff;
    case PitchBend;
    case ControlChange;
}
