<?php

namespace App\Services\AlertSender;

class MessageSanitizer
{
    public static function sanitize(string $message): string
    {
        $message = preg_replace('/[^\P{C}\n]+/u', '', $message);
        $message = trim($message);
        return mb_substr(mb_convert_encoding($message, 'UTF-8', 'auto'), 0, 1600);
    }
}
