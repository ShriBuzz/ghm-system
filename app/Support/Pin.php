<?php

namespace App\Support;

use Illuminate\Support\Facades\Config;

class Pin
{
    /**
     * A random numeric PIN (P7). Show it once; store only Hash::make() of it.
     */
    public static function generate(): string
    {
        $length = Config::integer('ghm.pin_length');

        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }
}
