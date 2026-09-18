<?php

declare(strict_types=1);

/**
 * Locale-aware date formatting helper.
 *
 * Used by the `date_localized` Volt helper registered in services.php to render
 * dates in the active UI locale's long format when the PHP `intl` extension is
 * available. Falls back to a plain `Y-m-d` representation when `intl` is missing
 * so the UI never breaks.
 */

if (!function_exists('format_date_localized')) {
    function format_date_localized(string $value, string $locale): string
    {
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }

        // Graceful fallback when the intl extension is not installed
        if (!class_exists('IntlDateFormatter')) {
            return date('Y-m-d', $ts);
        }

        $formatter = new \IntlDateFormatter(
            $locale,
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE,
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );

        $result = $formatter->format($ts);
        return $result !== false ? $result : date('Y-m-d', $ts);
    }
}
