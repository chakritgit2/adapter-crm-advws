<?php

declare(strict_types=1);

/**
 * Canonical organization job-level definitions.
 *
 * Levels are grouped into three categories (Executive, Management, Officer)
 * and ordered from highest rank to lowest within each category.
 *
 * Source: resource/mdSource/Position-Job-Levels.md
 */
class JobLevels
{
    public const CATEGORY_EXECUTIVE = 'Executive';
    public const CATEGORY_MANAGEMENT = 'Management';
    public const CATEGORY_OFFICER = 'Officer';

    /**
     * Ordered list of job levels: code => [category, level name].
     */
    private const LEVELS = [
        'D5' => [self::CATEGORY_EXECUTIVE,  'Chief Executive Officer'],
        'D4' => [self::CATEGORY_EXECUTIVE,  'Chief x Officer'],
        'D3' => [self::CATEGORY_EXECUTIVE,  'Senior Director'],
        'D2' => [self::CATEGORY_EXECUTIVE,  'Director'],
        'D1' => [self::CATEGORY_EXECUTIVE,  'Assistant Director'],
        'M5' => [self::CATEGORY_MANAGEMENT, 'Senior Manager'],
        'M4' => [self::CATEGORY_MANAGEMENT, 'Manager'],
        'M3' => [self::CATEGORY_MANAGEMENT, 'Assistant Manager'],
        'M2' => [self::CATEGORY_MANAGEMENT, 'Section Manager'],
        'M1' => [self::CATEGORY_MANAGEMENT, 'Assistant Section Manager'],
        'O3' => [self::CATEGORY_OFFICER,    'Supervisor'],
        'O2' => [self::CATEGORY_OFFICER,    'Senior Officer'],
        'O1' => [self::CATEGORY_OFFICER,    'Officer'],
    ];

    /**
     * All level codes in rank order (highest first).
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::LEVELS);
    }

    /**
     * Full level list grouped by category, ordered by rank.
     *
     * @return array<string, list<array{code: string, name: string}>>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::LEVELS as $code => [$category, $name]) {
            $grouped[$category][] = ['code' => $code, 'name' => $name];
        }
        return $grouped;
    }

    /**
     * Flat list of levels in rank order.
     *
     * @return list<array{code: string, category: string, name: string}>
     */
    public static function all(): array
    {
        $list = [];
        foreach (self::LEVELS as $code => [$category, $name]) {
            $list[] = ['code' => $code, 'category' => $category, 'name' => $name];
        }
        return $list;
    }

    /**
     * Code-keyed map: code => [category, name].
     *
     * @return array<string, array{category: string, name: string}>
     */
    public static function map(): array
    {
        $map = [];
        foreach (self::LEVELS as $code => [$category, $name]) {
            $map[$code] = ['category' => $category, 'name' => $name];
        }
        return $map;
    }

    public static function isValid(?string $code): bool
    {
        return $code !== null && array_key_exists($code, self::LEVELS);
    }

    public static function categoryOf(?string $code): ?string
    {
        if ($code === null || !array_key_exists($code, self::LEVELS)) {
            return null;
        }
        return self::LEVELS[$code][0];
    }

    public static function nameOf(?string $code): ?string
    {
        if ($code === null || !array_key_exists($code, self::LEVELS)) {
            return null;
        }
        return self::LEVELS[$code][1];
    }
}
