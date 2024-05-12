<?php

declare(strict_types=1);

namespace Nektria\Util;

use Nektria\Dto\Clock;
use Nektria\Exception\InvalidRequestParamException;
use Nektria\Exception\MissingRequestParamException;
use Throwable;

use function count;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;

class ArrayDataFetcher
{
    /**
     * @param mixed[] $data
     */
    public function __construct(protected readonly array $data)
    {
    }

    /**
     * @return mixed[]
     */
    public function data(): array
    {
        return $this->data;
    }

    public function hasField(string $field): bool
    {
        return $this->getValue($field) !== null;
    }

    protected function getValue(string $field): mixed
    {
        if (!str_contains($field, '.')) {
            $value = $this->data[$field] ?? null;

            if ($value === 'null') {
                return null;
            }

            if ($value === 'true') {
                return true;
            }

            if ($value === 'false') {
                return false;
            }

            if (is_string($value)) {
                return StringUtil::trim($value);
            }

            return $value;
        }

        $fieldParts = explode('.', $field);
        $currentValue = $this->data[$fieldParts[0]] ?? null;
        $length = count($fieldParts);

        if (!is_array($currentValue)) {
            return null;
        }

        for ($i = 1; $i < $length; ++$i) {
            $part = $fieldParts[$i];

            if ($i === ($length - 1)) {
                $value = $currentValue[$part] ?? null;

                if ($value === 'null') {
                    return null;
                }

                if ($value === 'true') {
                    return true;
                }

                if ($value === 'false') {
                    return false;
                }

                if (is_string($value)) {
                    return StringUtil::trim($value);
                }

                return $value;
            }

            $currentValue = $currentValue[$part] ?? null;

            if (!is_array($currentValue)) {
                return null;
            }
        }

        return null;
    }

    public function getLength(string $field): int
    {
        $fieldParts = explode('.', $field);
        $currentValue = $this->data[$fieldParts[0]] ?? null;
        $length = count($fieldParts);

        if ($length === 1) {
            if ($currentValue === null) {
                return 0;
            }

            if (!is_array($currentValue)) {
                return 0;
            }

            return count($currentValue);
        }

        if (!is_array($currentValue)) {
            return 0;
        }

        for ($i = 1; $i < $length; ++$i) {
            $part = $fieldParts[$i];
            $currentValue = $currentValue[$part] ?? null;

            if (!is_array($currentValue)) {
                return 0;
            }

            if ($i === $length - 1) {
                return count($currentValue);
            }
        }

        return 0;
    }

    public function retrieveLength(string $field): int
    {
        $fieldParts = explode('.', $field);
        $currentValue = $this->data[$fieldParts[0]] ?? null;
        $length = count($fieldParts);
        $acummulative = $fieldParts[0];

        if ($length === 1) {
            if ($currentValue === null) {
                throw new MissingRequestParamException($field);
            }

            if (!is_array($currentValue)) {
                throw new InvalidRequestParamException($acummulative, 'array');
            }

            return count($currentValue);
        }

        if (!is_array($currentValue)) {
            if (is_numeric($fieldParts[1])) {
                throw new InvalidRequestParamException($acummulative, 'array');
            }

            throw new InvalidRequestParamException($acummulative, 'object');
        }

        for ($i = 1; $i < $length; ++$i) {
            $part = $fieldParts[$i];
            $acummulative .= ".{$part}";

            $currentValue = $currentValue[$part] ?? null;

            if ($i === $length - 1) {
                if (!is_array($currentValue)) {
                    throw new InvalidRequestParamException($acummulative, 'array');
                }

                return count($currentValue);
            }

            if (!is_array($currentValue)) {
                if (is_numeric($fieldParts[$i + 1])) {
                    throw new InvalidRequestParamException($acummulative, 'array');
                }

                throw new InvalidRequestParamException($acummulative, 'object');
            }
        }

        return 0;
    }

    public function getString(string $field): ?string
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new InvalidRequestParamException($field, 'string');
        }

        return StringUtil::trim($value);
    }

    /**
     * @return mixed[]|null
     */
    public function getArray(string $field): ?array
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            throw new InvalidRequestParamException($field, 'array');
        }

        return $value;
    }

    /**
     * @return string[]|null
     */
    public function getStringArray(string $field): ?array
    {
        if (!$this->hasField($field)) {
            return null;
        }

        $ret = [];
        $length = $this->retrieveLength($field);
        for ($i = 0; $i < $length; ++$i) {
            $ret[] = $this->retrieveString("{$field}.{$i}");
        }

        return $ret;
    }

    /**
     * @return string[]
     */
    public function retrieveStringArray(string $field): array
    {
        $value = $this->getStringArray($field);
        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    /**
     * @return mixed[]
     */
    public function retrieveArray(string $field): array
    {
        $value = $this->getArray($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveString(string $field): string
    {
        $value = $this->getString($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function getInt(string $field): ?int
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (is_array($value) || is_object($value)) {
            throw new InvalidRequestParamException($field, 'int');
        }

        if (((string) (int) $value) !== (string) $value) {
            throw new InvalidRequestParamException($field, 'int');
        }

        return max(-2147483648, min((int) $value, 2147483647));
    }

    public function getBool(string $field): ?bool
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if ($value === 'true' || $value === 'false') {
            return $value === 'true';
        }

        if (is_bool($value)) {
            return $value;
        }

        throw new InvalidRequestParamException($field, 'bool');
    }

    public function retrieveBool(string $field): bool
    {
        $value = $this->getBool($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function retrieveInt(string $field): int
    {
        $value = $this->getInt($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function getFloat(string $field): ?float
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new InvalidRequestParamException($field, 'float');
        }

        return (float) $value;
    }

    public function retrieveFloat(string $field): float
    {
        $value = $this->getFloat($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function getClock(string $field): ?Clock
    {
        $value = $this->getValue($field);

        if ($value === null) {
            return null;
        }

        try {
            return Clock::fromString($value)->setTimezone('UTC');
        } catch (Throwable) {
            throw new InvalidRequestParamException($field, 'datetime');
        }
    }

    public function retrieveClock(string $field): Clock
    {
        $value = $this->getClock($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value;
    }

    public function getClockAsLocal(string $field, string $timezone): ?Clock
    {
        $value = $this->getClock($field);

        return $value?->replaceTimezone($timezone)->removeTimeZone();
    }

    public function retrieveClockAsLocal(string $field, string $timezone): Clock
    {
        $value = $this->getClock($field);

        if ($value === null) {
            throw new MissingRequestParamException($field);
        }

        return $value->replaceTimezone($timezone)->removeTimeZone();
    }

    /**
     * @return float[]|null
     */
    public function getCoordinates(string $field): ?array
    {
        if ($this->hasField($field)) {
            return [
                $this->retrieveFloat("{$field}.latitude"),
                $this->retrieveFloat("{$field}.longitude"),
            ];
        }

        return null;
    }
}
