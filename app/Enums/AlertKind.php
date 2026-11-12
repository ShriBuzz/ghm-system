<?php

namespace App\Enums;

/**
 * Why staff are alerted to a case (N1, U6). Each kind has its own email subject and line.
 */
enum AlertKind: string
{
    case NewCase = 'new_case';
    case Message = 'message';
    case Critical = 'critical';
    case PastTarget = 'past_target';
}
