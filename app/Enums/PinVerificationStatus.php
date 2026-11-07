<?php

namespace App\Enums;

enum PinVerificationStatus: string
{
    case Valid = 'valid';
    case Missing = 'missing';
    case Invalid = 'invalid';
    case Locked = 'locked';
}
