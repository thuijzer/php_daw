<?php

namespace Thuijzer\PhpDaw\Enums;

enum ControlChange: int
{
    case Volume = 7;
    case Pan = 10;
    case Pedal = 64;
    case Modulation = 1;
    case AttackTime = 73;
    case DecayTime = 75;
    case SustainLevel = 79;
    case ReleaseTime = 72;

}