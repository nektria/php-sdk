<?php

declare(strict_types=1);

namespace Nektria\Dto;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use Nektria\Exception\NektriaException;
use Throwable;

/**
 * @phpstan-type CtTimeFormat 'seconds'|'minutes'|'hours'|'days'|'weeks'|'months'|'years'
 */
class Clock
{
    private DateTimeImmutable $dateTime;

    private function __construct(?DateTimeImmutable $dateTime = null)
    {
        $this->dateTime = $dateTime ?? new DateTimeImmutable();
    }

    public static function fromPhpDateTime(DateTimeImmutable $dateTime): self
    {
        return new self($dateTime);
    }

    public static function fromString(string $dateTime): self
    {
        try {
            return new self(new DateTimeImmutable($dateTime));
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public static function fromStringOrNull(?string $dateTime): ?self
    {
        if ($dateTime === null) {
            return null;
        }

        try {
            return new self(new DateTimeImmutable($dateTime));
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public static function max(self $a, self $b): self
    {
        return $a->isAfter($b) ? $a : $b;
    }

    public static function min(self $a, self $b): self
    {
        return $a->isBefore($b) ? $a : $b;
    }

    public static function now(): self
    {
        return new self();
    }

    /**
     * @param CtTimeFormat $in
     * @return Clock[]
     */
    public static function sequence(self $from, self $to, string $in = 'days', bool $includeLast = false): array
    {
        $ret = [];

        $current = $from;
        while ($current->isBefore($to, $in)) {
            $ret[] = $current;
            $current = new self($current->dateTime->modify("+1 {$in}"));
        }

        if ($includeLast) {
            $ret[] = $to;
        }

        return $ret;
    }

    public function __toString(): string
    {
        return $this->dateTimeString();
    }

    /**
     * @param CtTimeFormat $in
     */
    public function add(int $amount, string $in = 'seconds'): self
    {
        if ($amount === 0) {
            return $this;
        }

        if ($amount > 0) {
            return new self($this->dateTime->modify("+ {$amount} {$in}"));
        }

        $amount = -$amount;

        return new self($this->dateTime->modify("- {$amount} {$in}"));
    }

    /**
     * @param CtTimeFormat $in
     */
    public function compare(self $to, string $in = 'seconds'): int
    {
        return $this->timestamp($in) <=> $to->timestamp($in);
    }

    // still is UTC but the hour is the same as the timezone selected

    public function dateString(?string $timeZone = null): string
    {
        try {
            $dateTime = $this->dateTime;
            if ($timeZone !== null) {
                $dateTime = $dateTime->setTimezone(new DateTimeZone($timeZone));
            }

            return $dateTime->format('Y-m-d');
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function dateTimeString(?string $timeZone = null): string
    {
        try {
            $dateTime = $this->dateTime;
            if ($timeZone !== null) {
                $dateTime = $dateTime->setTimezone(new DateTimeZone($timeZone));
            }

            return $dateTime->format('Y-m-d\TH:i:s');
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function day(): string
    {
        return $this->dateTime->format('d');
    }

    /**
     * @param CtTimeFormat $in
     */
    public function diff(self $from, string $in = 'seconds'): int
    {
        $diff = $this->dateTime->diff($from->dateTime);

        $absDiff = match ($in) {
            'seconds' => $diff->s + ($diff->i * 60) + ($diff->h * 3600) + ((int) $diff->days * 86400),
            'minutes' => $diff->i + ($diff->h * 60) + ((int) $diff->days * 1440),
            'hours' => $diff->h + ((int) $diff->days * 24),
            'days' => (int) $diff->days,
            'weeks' => (int) ((int) $diff->days / 7),
            'months' => $diff->m + ($diff->y * 12),
            default => $diff->y
        };

        return $diff->invert === 1 ? $absDiff : -$absDiff;
    }

    public function fromUTCToLocal(string $timezone): LocalClock
    {
        return LocalClock::fromString($this->setTimezone($timezone)->replaceTimezone('UTC')->dateTimeString());
    }

    public function getPHPDateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function hour(): string
    {
        return $this->dateTime->format('H');
    }

    /**
     * @param CtTimeFormat $in
     */
    public function isAfter(self $to, string $in = 'seconds'): bool
    {
        return $this->timestamp($in) > $to->timestamp($in);
    }

    /**
     * @param CtTimeFormat $in
     */
    public function isAfterOrEqual(self $to, string $in = 'seconds'): bool
    {
        return $this->timestamp($in) >= $to->timestamp($in);
    }

    /**
     * @param CtTimeFormat $in
     */
    public function isBefore(self $to, string $in = 'seconds'): bool
    {
        return $this->timestamp($in) < $to->timestamp($in);
    }

    /**
     * @param CtTimeFormat $in
     */
    public function isBeforeOrEqual(self $to, string $in = 'seconds'): bool
    {
        return $this->timestamp($in) <= $to->timestamp($in);
    }

    /**
     * @param CtTimeFormat $in
     */
    public function isEqual(self $to, string $in = 'seconds'): bool
    {
        return $this->timestamp($in) === $to->timestamp($in);
    }

    /**
     * @param CtTimeFormat $in
     */
    public function isNotEqual(self $to, string $in = 'seconds'): bool
    {
        return $this->timestamp($in) !== $to->timestamp($in);
    }

    public function iso8601String(?string $timeZone = null): string
    {
        try {
            $dateTime = $this->dateTime;
            if ($timeZone !== null) {
                $dateTime = $dateTime->setTimezone(new DateTimeZone($timeZone));
            }

            return $dateTime->format(DateTimeImmutable::ATOM);
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function microDateTimeString(?string $timeZone = null): string
    {
        try {
            $dateTime = $this->dateTime;
            if ($timeZone !== null) {
                $dateTime = $dateTime->setTimezone(new DateTimeZone($timeZone));
            }

            return $dateTime->format('Y-m-d\TH:i:s.u');
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function modify(string $modifier): self
    {
        return new self($this->dateTime->modify($modifier));
    }

    public function month(): string
    {
        return $this->dateTime->format('m');
    }

    public function removeTimeZone(): self
    {
        return self::fromString($this->dateTimeString());
    }

    public function replaceTimezone(string $timeZone): self
    {
        try {
            $dateTime = (new DateTimeImmutable($this->dateTimeString(), new DateTimeZone($timeZone)))
                ->setTimezone(new DateTimeZone('UTC'));

            return new self($dateTime);
        } catch (Throwable $e) {
            throw new DomainException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function setTime(int $hour, int $minutes = 0): self
    {
        return new self($this->dateTime->setTime($hour, $minutes));
    }

    public function setTimestamp(int $timestamp): self
    {
        return new self($this->dateTime->setTimestamp($timestamp));
    }

    public function setTimezone(string $timeZone): self
    {
        try {
            return new self($this->dateTime->setTimezone(new DateTimeZone($timeZone)));
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    public function setYearAndWeek(int $year, int $week): self
    {
        return new self($this->dateTime->setISODate($year, $week));
    }

    public function sinceString(): string
    {
        $since = $this->dateTime->getTimestamp() - time();

        $chunks = [
            [60 * 60 * 24 * 365, 'year'],
            [60 * 60 * 24 * 30, 'month'],
            [60 * 60 * 24 * 7, 'week'],
            [60 * 60 * 24, 'day'],
            [60 * 60, 'hour'],
            [60, 'minute'],
            [1, 'second'],
        ];

        $count = 0;
        $name = '';

        foreach ($chunks as $iValue) {
            [$seconds, $name] = $iValue;
            $count = (int) ($since / $seconds);
            if ($count !== 0) {
                break;
            }
        }

        if ($count === 0) {
            return 'just now';
        }

        if ($count === 1) {
            return "1 {$name} ago";
        }

        return "$count {$name}s ago";
    }

    public function timeString(bool $withSeconds = false, ?string $timeZone = null): string
    {
        try {
            $dateTime = $this->dateTime;
            if ($timeZone !== null) {
                $dateTime = $dateTime->setTimezone(new DateTimeZone($timeZone));
            }

            if ($withSeconds) {
                return $dateTime->format('H:i:s');
            }

            return $dateTime->format('H:i');
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }
    }

    /**
     * @param CtTimeFormat $in
     */
    public function timestamp(string $in = 'seconds'): int
    {
        $ts = $this->dateTime->getTimestamp();

        return match ($in) {
            'seconds' => $ts,
            'minutes' => (int) ($ts / 60),
            'hours' => (int) ($ts / 3600),
            'days' => (int) ($ts / 86400),
            'weeks' => (int) ($ts / 604800),
            default => throw new DomainException("Invalid time format: {$in}"),
        };
    }

    public function week(): string
    {
        return $this->dateTime->format('W');
    }

    public function year(): string
    {
        return $this->dateTime->format('Y');
    }
}
