<?php

namespace Classes;


class Helper
{
    public static function persianToEnglish($input)
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '٤', '۵', '٥', '٦', '۶', '۷', '۸', '۹'];
        $english = [ 0 ,  1 ,  2 ,  3 ,  4 ,  4 ,  5 ,  5 ,  6 ,  6 ,  7 ,  8 ,  9 ];
        return str_replace($persian, $english, $input);
    }

    public static function env($filePath = '.env') {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignore comments and empty lines
            if (str_starts_with(trim($line), '#') || empty(trim($line))) {
                continue;
            }

            // Parse the line into key and value
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes from value if present
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            putenv(sprintf('%s=%s', $key, $value));
        }
        return true;
    }
}