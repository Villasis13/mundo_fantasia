<?php

namespace App\Support\Legacy;

class QrLib
{
    public static function load(): void
    {
        if (!class_exists(\QRcode::class, false)) {
            require_once base_path('libs/phpqrcode/qrlib.php');
        }
    }
}
