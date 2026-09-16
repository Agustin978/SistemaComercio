<?php

namespace App\Reporting\Enums;

enum LogOrigin: string
{
    case Emitter = 'emitter';
    case Hub = 'hub';
}
