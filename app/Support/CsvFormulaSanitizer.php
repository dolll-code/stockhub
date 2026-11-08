<?php

namespace App\Support;

class CsvFormulaSanitizer
{
    /**
     * Sanitize string values to prevent spreadsheet formula injection (SEC-10, AC-RPT-05).
     * Strings starting with '=', '+', '-', '@', "\t", or "\r" are prefixed with an apostrophe.
     */
    public static function sanitize(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        $disallowed = ['=', '+', '-', '@', "\t", "\r"];
        if (in_array($value[0], $disallowed, true)) {
            return "'".$value;
        }

        return $value;
    }
}
