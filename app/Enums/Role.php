<?php

namespace App\Enums;

enum Role: string
{
    case Administrator = 'administrator';
    case FocalPerson = 'focal_person';
    case RestrictedOfficer = 'restricted_officer';
}
