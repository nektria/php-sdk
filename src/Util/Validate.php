<?php

declare(strict_types=1);

namespace Nektria\Util;

use InvalidArgumentException;
use Nektria\Dto\Clock;
use Nektria\Exception\MissingFieldRequiredToCreateClassException;
use RuntimeException;
use Throwable;

use function count;
use function in_array;
use function strlen;

use const FILTER_VALIDATE_EMAIL;

class Validate
{
    // dates

    public static function date(string $date): void
    {
        $parsed = Clock::fromString($date);

        if ($parsed->dateString() !== $date) {
            throw new InvalidArgumentException("Invalid date '{$date}'.");
        }
    }

    // numbers

    public static function naturalNumber(int | float $number): void
    {
        if ($number < 0) {
            throw new InvalidArgumentException("Invalid positive number '{$number}'.");
        }
    }

    public static function greaterThan(
        int | float | string $number,
        int | float | string $limit,
        ?string $message = null
    ): void {
        $message ??= "Invalid '{$number}', must be greater than {$limit}.";
        if ((string) $number <= (string) $limit) {
            throw new InvalidArgumentException($message);
        }
    }

    public static function lessThan(int | float $number, int | float $limit): void
    {
        if ($number >= $limit) {
            throw new InvalidArgumentException("Invalid '{$number}', must be less than {$limit}.");
        }
    }

    public static function percentileNumber(int | float $number): void
    {
        if ($number < 0 || $number > 1) {
            throw new InvalidArgumentException("Invalid percentile number '{$number}', must be >= 0 and <= 1.");
        }
    }

    // Strings

    public static function notEmpty(string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException("Invalid string '{$value}', must not be empty.");
        }
    }

    public static function regexp(string $value, string $regexp): void
    {
        if (preg_match($regexp, $value) === false) {
            throw new InvalidArgumentException("Invalid string '{$value}', does not match with '{$regexp}'.");
        }
    }

    public static function minLength(string $value, int $length): void
    {
        if (strlen($value) < $length) {
            throw new InvalidArgumentException(
                "Invalid string '{$value}', must be be at least {$length} characters long."
            );
        }
    }

    public static function maxLength(string $value, int $length): void
    {
        if (strlen($value) > $length) {
            throw new InvalidArgumentException(
                "Invalid string '{$value}', must be as maximum {$length} characters long."
            );
        }
    }

    public static function email(string $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException(
                "Invalid email '{$value}'."
            );
        }
    }

    public static function uuid4(string $id): void
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id) !== 1) {
            throw new InvalidArgumentException("Invalid uuid '{$id}'.");
        }
    }

    public static function dniNie(string $dninie): void
    {
        if (!self::isValidNIE($dninie) && !self::isValidNIF($dninie)) {
            throw new InvalidArgumentException("Invalid DNI or NIE '{$dninie}'.");
        }
    }

    public static function isValidNIF(string $docNumber): bool
    {
        $fixedDocNumber = strtoupper($docNumber);
        $writtenDigit = strtoupper($docNumber[strlen($docNumber) - 1]);
        $isValidFormat = self::respectsDocPattern(
            $fixedDocNumber,
            '/^[KLM0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][a-zA-Z0-9]/'
        );

        if ($isValidFormat) {
            $correctDigit = self::getNIFCheckDigit($fixedDocNumber);

            if ($writtenDigit === $correctDigit) {
                return true;
            }
        }

        return false;
    }

    public static function isValidNIE(string $docNumber): bool
    {
        $fixedDocNumber = strtoupper($docNumber);
        $isValidFormat = self::respectsDocPattern(
            $fixedDocNumber,
            '/^[XYZT][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z0-9]/'
        );

        if ($isValidFormat) {
            if ($fixedDocNumber[1] === 'T') {
                return true;
            }

            $numberWithoutLast = substr($fixedDocNumber, 0, -1);
            $lastDigit = substr($fixedDocNumber, strlen($fixedDocNumber) - 1, strlen($fixedDocNumber));
            $numberWithoutLast = str_replace(['Y', 'X', 'Z'], ['1', '0', '2'], $numberWithoutLast);
            $fixedDocNumber = $numberWithoutLast . $lastDigit;

            return self::isValidNIF($fixedDocNumber);
        }

        return false;
    }

    private static function getNIFCheckDigit(string $docNumber): string
    {
        $keyString = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $correctLetter = '';

        $fixedDocNumber = strtoupper($docNumber);

        $isValid = self::respectsDocPattern(
            $fixedDocNumber,
            '/^[KLM0-9][0-9][0-9][0-9][0-9][0-9][0-9][0-9][a-zA-Z0-9]/'
        );

        if ($isValid) {
            $fixedDocNumber = str_replace(['K', 'L', 'M'], '0', $fixedDocNumber);

            $position = substr($fixedDocNumber, 0, 8) % 23;
            $correctLetter = $keyString[$position];
        }

        return $correctLetter;
    }

    private static function respectsDocPattern(string $givenString, string $pattern): bool
    {
        return preg_match($pattern, strtoupper($givenString)) !== false;
    }

    // coordinates

    public static function latitude(float $value): void
    {
        if ($value < -90 || $value > 90) {
            throw new InvalidArgumentException("Invalid latitude '{$value}'");
        }
    }

    public static function longitude(float $value): void
    {
        if ($value < -180 || $value > 180) {
            throw new InvalidArgumentException("Invalid longitude '{$value}'");
        }
    }

    /**
     * @param string[] $values
     */
    public static function inStringList(string $value, array $values): void
    {
        if (!in_array($value, $values, true)) {
            $validValues = implode(', ', $values);

            throw new InvalidArgumentException("Invalid value '{$value}', valid values are '{$validValues}'");
        }
    }

    public static function color(string $value): void
    {
        if (preg_match('/#([a-f0-9]{3}){1,2}\b/i', $value) === false) {
            throw new InvalidArgumentException("Invalid color '{$value}'");
        }
    }

    // times

    public static function timeRange(Clock $start, Clock $end): void
    {
        if ($start->isAfter($end)) {
            throw new InvalidArgumentException('Invalid timeRange, endTime must be after startTime');
        }
    }

    public static function sameDay(Clock $start, Clock $end): void
    {
        if ($start->dateString() !== $end->dateString()) {
            throw new InvalidArgumentException('Invalid timeRange, startTime and endTime must be in the same day');
        }
    }

    public static function timezone(string $value): void
    {
        try {
            Clock::new()->setTimezone($value);
        } catch (Throwable $e) {
            throw new InvalidArgumentException("Invalid canonical timezone '{$value}'", $e->getCode(), $e);
        }
    }

    // array

    /**
     * @param mixed[] $list
     */
    public static function minArrayLength(array $list, int $length): void
    {
        if (count($list) < $length) {
            throw new InvalidArgumentException("Invalid list, must be be at least {$length} long.");
        }
    }

    // classes

    /**
     * @param string[] $fields
     */
    public static function classFieldsReturnsNotNull(object $object, string $className, array $fields): void
    {
        foreach ($fields as $field) {
            self::checkClassFieldReturnsNotNull($object, $className, $field);
        }
    }

    public static function classFieldReturnsNotNull(string $className, string $name, mixed $field): void
    {
        if ($field === null) {
            throw new MissingFieldRequiredToCreateClassException($className, $name);
        }
    }

    private static function checkClassFieldReturnsNotNull(object $object, string $className, string $field): void
    {
        if (method_exists($object, $field)) {
            try {
                /* @phpstan-ignore-next-line */
                if ($object->{$field}() === null) {
                    throw new MissingFieldRequiredToCreateClassException($className, $field);
                }
            } catch (Throwable $e) {
                if ($e instanceof MissingFieldRequiredToCreateClassException) {
                    throw $e;
                }

                throw new RuntimeException("{$className} does not implements {$field}()");
            }
        } elseif (property_exists($object, $field)) {
            /* @phpstan-ignore-next-line */
            if ($object->{$field} === null) {
                throw new MissingFieldRequiredToCreateClassException($className, $field);
            }
        } else {
            throw new RuntimeException("{$className} does not implements {$field}()");
        }
    }
}
