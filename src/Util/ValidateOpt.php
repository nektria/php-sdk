<?php

declare(strict_types=1);

namespace Nektria\Util;

class ValidateOpt
{
    // dates

    public static function date(?string $date): void
    {
        if ($date !== null) {
            Validate::date($date);
        }
    }

    // numbers

    public static function naturalNumber(int | float | null $number): void
    {
        if ($number !== null) {
            Validate::naturalNumber($number);
        }
    }

    public static function greaterThan(int | float | null $number, int | float $limit): void
    {
        if ($number !== null) {
            Validate::greaterThan($number, $limit);
        }
    }

    public static function lessThan(int | float | null $number, int | float $limit): void
    {
        if ($number !== null) {
            Validate::lessThan($number, $limit);
        }
    }

    public static function percentileNumber(int | float | null $number): void
    {
        if ($number !== null) {
            Validate::percentileNumber($number);
        }
    }

    // Strings

    public static function notEmpty(?string $value): void
    {
        if ($value !== null) {
            Validate::notEmpty($value);
        }
    }

    public static function regexp(?string $value, string $regexp): void
    {
        if ($value !== null) {
            Validate::regexp($value, $regexp);
        }
    }

    public static function minLength(?string $value, int $length): void
    {
        if ($value !== null) {
            Validate::minLength($value, $length);
        }
    }

    public static function maxLength(?string $value, int $length): void
    {
        if ($value !== null) {
            Validate::maxLength($value, $length);
        }
    }

    public static function email(?string $value): void
    {
        if ($value !== null) {
            Validate::email($value);
        }
    }

    public static function uuid4(?string $value): void
    {
        if ($value !== null) {
            Validate::uuid4($value);
        }
    }

    public static function dniNie(?string $value): void
    {
        if ($value !== null) {
            Validate::dniNie($value);
        }
    }

    public static function role(?string $value): void
    {
        if ($value !== null) {
            Validate::role($value);
        }
    }

    // coordinates

    public static function latitude(?float $value): void
    {
        if ($value !== null) {
            Validate::latitude($value);
        }
    }

    public static function longitude(?float $value): void
    {
        if ($value !== null) {
            Validate::longitude($value);
        }
    }

    /**
     * @param string[] $validValues
     */
    public static function inStringList(?string $value, array $validValues): void
    {
        if ($value !== null) {
            Validate::inStringList($value, $validValues);
        }
    }

    public static function color(?string $value): void
    {
        if ($value !== null) {
            Validate::color($value);
        }
    }

    // times

    public static function timezone(?string $value): void
    {
        if ($value !== null) {
            Validate::timezone($value);
        }
    }

    // array

    /**
     * @param mixed[]|null $list
     */
    public static function minArrayLength(?array $list, int $length): void
    {
        if ($list !== null) {
            Validate::minArrayLength($list, $length);
        }
    }
}
