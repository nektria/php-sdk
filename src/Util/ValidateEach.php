<?php

declare(strict_types=1);

namespace Nektria\Util;

class ValidateEach
{
    // dates
    /**
     * @param string[] $values
     */
    public static function date(array $values): void
    {
        foreach ($values as $item) {
            Validate::date($item);
        }
    }

    // numbers

    /**
     * @param int[]|float[] $values
     */
    public static function naturalNumber(array $values): void
    {
        foreach ($values as $item) {
            Validate::naturalNumber($item);
        }
    }

    /**
     * @param int[]|float[]|string[] $values
     */
    public static function greaterThan(array $values, int | float | string $limit): void
    {
        foreach ($values as $item) {
            Validate::greaterThan($item, $limit);
        }
    }

    /**
     * @param int[]|float[] $values
     */
    public static function lessThan(array $values, int | float $limit): void
    {
        foreach ($values as $item) {
            Validate::lessThan($item, $limit);
        }
    }

    /**
     * @param int[]|float[] $values
     */
    public static function percentileNumber(array $values): void
    {
        foreach ($values as $item) {
            Validate::percentileNumber($item);
        }
    }

    // Strings

    /**
     * @param string[] $values
     */
    public static function notEmpty(array $values): void
    {
        foreach ($values as $item) {
            Validate::notEmpty($item);
        }
    }

    /**
     * @param string[] $values
     */
    public static function regexp(array $values, string $regexp): void
    {
        foreach ($values as $item) {
            Validate::regexp($item, $regexp);
        }
    }

    /**
     * @param string[] $values
     */
    public static function minLength(array $values, int $length): void
    {
        foreach ($values as $item) {
            Validate::minLength($item, $length);
        }
    }

    /**
     * @param string[] $values
     */
    public static function maxLength(array $values, int $length): void
    {
        foreach ($values as $item) {
            Validate::maxLength($item, $length);
        }
    }

    /**
     * @param string[] $values
     */
    public static function email(array $values): void
    {
        foreach ($values as $item) {
            Validate::email($item);
        }
    }

    /**
     * @param string[] $values
     */
    public static function uuid4(array $values): void
    {
        foreach ($values as $item) {
            Validate::uuid4($item);
        }
    }

    /**
     * @param string[] $values
     */
    public static function dniNie(array $values): void
    {
        foreach ($values as $item) {
            Validate::dniNie($item);
        }
    }

    // coordinates

    /**
     * @param float[] $values
     */
    public static function latitude(array $values): void
    {
        foreach ($values as $item) {
            Validate::latitude($item);
        }
    }

    /**
     * @param float[] $values
     */
    public static function longitude(array $values): void
    {
        foreach ($values as $item) {
            Validate::longitude($item);
        }
    }

    /**
     * @param string[] $values
     * @param string[] $validValues
     */
    public static function inStringList(array $values, array $validValues): void
    {
        foreach ($values as $item) {
            Validate::inStringList($item, $validValues);
        }
    }

    /**
     * @param string[] $values
     */
    public static function color(array $values): void
    {
        foreach ($values as $item) {
            Validate::color($item);
        }
    }

    // times

    /**
     * @param string[] $values
     */
    public static function timezone(array $values): void
    {
        foreach ($values as $item) {
            Validate::timezone($item);
        }
    }

    // array

    /**
     * @param mixed[][] $values
     */
    public static function minArrayLength(array $values, int $length): void
    {
        foreach ($values as $item) {
            Validate::minArrayLength($item, $length);
        }
    }
}
