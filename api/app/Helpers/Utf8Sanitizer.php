<?php

namespace App\Helpers;

/**
 * POCOR-9847
 * Defensive cleanup for API responses built from data that may still contain malformed UTF-8
 * byte sequences (e.g. legacy utf8mb3 columns, or rows written before the charset was fixed).
 * Without this, Laravel's JsonResponse::setData() throws InvalidArgumentException("Malformed
 * UTF-8 characters, possibly incorrectly encoded") and the endpoint returns a 500 instead of data.
 */
class Utf8Sanitizer
{
    public static function clean(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map([self::class, 'clean'], $data);
        }

        if (is_string($data)) {
            if (mb_check_encoding($data, 'UTF-8')) {
                return $data;
            }

            // Not valid UTF-8 -- most likely a legacy Latin-1/Windows-1252 value
            // (common for text written before a column's charset was fixed, or by
            // non-UTF-8-aware clients). Re-encoding from that assumption recovers
            // the real characters; a straight UTF-8 -> UTF-8 convert would instead
            // replace each invalid byte with a literal '?', destroying the value.
            $recovered = @mb_convert_encoding($data, 'UTF-8', 'Windows-1252');

            return $recovered !== false && mb_check_encoding($recovered, 'UTF-8')
                ? $recovered
                : mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        return $data;
    }
}
