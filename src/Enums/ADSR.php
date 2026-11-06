<?php

namespace Thuijzer\PhpDaw\Enums;

enum ADSR
{
    case Idle;
    case Attack;
    case Decay;
    case Sustain;
    case Release;
}
