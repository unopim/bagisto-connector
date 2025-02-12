<?php

namespace Webkul\SunskyOnline\Helpers;

class CommonHelper
{
    public static function generateCode($label)
    {
        $code = strtolower(preg_replace('/[^A-Za-z0-9]+/', '_', $label));

        return $code;
    }
}
