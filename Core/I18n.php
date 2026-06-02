<?php

declare(strict_types=1);

namespace Core;

class I18n
{
    private static array $translations = [];
    private static string $lang = 'en';

    /**
     * Initialize I18n with English.
     */
    public static function init(): void
    {
        self::$lang = 'en';
        $file = __DIR__ . "/../lang/en.php";
        if (file_exists($file)) {
            self::$translations = require $file;
        }
    }

    /**
     * Get translation for a key.
     */
    public static function t(string $key, array $placeholders = []): string
    {
        $text = self::$translations[$key] ?? $key;
        foreach ($placeholders as $k => $v) {
            $text = str_replace("{{$k}}", (string)$v, $text);
        }
        return $text;
    }

    /**
     * Get current language.
     */
    public static function getLang(): string
    {
        return 'en';
    }
}
