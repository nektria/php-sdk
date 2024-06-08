<?php

declare(strict_types=1);

namespace Nektria\Test;

use Nektria\Exception\NektriaException;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use function gettype;
use function in_array;
use function is_array;

class TestCase extends WebTestCase
{
    protected KernelBrowser $client;

    private static bool $onBootExecuted = false;

    /**
     * @param mixed[] $expected
     * @param mixed[] $target
     */
    protected static function assertJsonArrayType(array $expected, array $target): void
    {
        foreach ($target as $key => $value) {
            self::assertJsonTypeInternal($expected, $value, "[{$key}]");
        }
    }

    /**
     * @param mixed[] $expected
     * @param mixed[] $target
     */
    protected static function assertJsonArrayV2Type(array $expected, array $target): void
    {
        self::assertJsonTypeInternal([
            'list[]' => $expected,
        ], $target, '');
    }

    /**
     * @param mixed[] $expected
     * @param mixed[] $target
     */
    protected static function assertJsonType(array $expected, array $target): void
    {
        self::assertJsonTypeInternal($expected, $target, '');
    }

    /**
     * @param mixed[] $expected
     * @param mixed[] $target
     */
    private static function assertJsonTypeInternal(array $expected, array $target, string $root): void
    {
        $expectedKeys = [];

        $root .= $root !== '' ? '.' : '';
        foreach ($expected as $key => $value) {
            $isOptional = strncmp($key, '?', 1) === 0;
            $isArray = str_ends_with($key, '[]');
            $isMatrix = str_ends_with($key, '[][]');
            $realKey = $isOptional ? substr($key, 1) : $key;
            if ($isMatrix) {
                $realKey = substr($realKey, 0, -4);
            } elseif ($isArray) {
                $realKey = substr($realKey, 0, -2);
            }
            $expectedKeys[] = $realKey;
            $rootKey = $root . $realKey;

            if (!$isOptional && ($target[$realKey] ?? null) === null) {
                throw new ExpectationFailedException("Field '{$rootKey}' is missing.");
            }

            if (($target[$realKey] ?? null) === null) {
                continue;
            }

            if ($isMatrix) {
                if (!is_array($target[$realKey])) {
                    throw new ExpectationFailedException("Field '{$rootKey}' is not an array.");
                }

                foreach ($target[$realKey] as $i => $iValue) {
                    if (!is_array($target[$realKey][$i])) {
                        throw new ExpectationFailedException("Field '{$rootKey}[{$i}]' is not an array.");
                    }

                    $index = (string) $i;
                    if (($iValue ?? null) === null) {
                        throw new ExpectationFailedException(
                            "Field '{$rootKey}[{$index}]' is missing.",
                        );
                    }

                    foreach ($target[$realKey][$i] as $j => $jValue) {
                        $jIndex = (string) $j;
                        if (($jValue ?? null) === null) {
                            throw new ExpectationFailedException(
                                "Field '{$rootKey}[{$index}][{$jIndex}]' is missing.",
                            );
                        }

                        if (is_array($jValue)) {
                            self::assertJsonTypeInternal(
                                $value,
                                $target[$realKey][$index][$jIndex],
                                "{$rootKey}[{$index}][{$jIndex}]",
                            );
                        } else {
                            $expectedTypes = explode('|', $value);
                            $receivedType = gettype($jValue);
                            $found = false;
                            foreach ($expectedTypes as $expectedType) {
                                if ($expectedType === $receivedType) {
                                    $found = true;

                                    break;
                                }

                                if ($expectedType === 'double' && $receivedType === 'integer') {
                                    $found = true;

                                    break;
                                }
                            }

                            if (!$found) {
                                $expectedType = $value;

                                throw new ExpectationFailedException(
                                    "Field '{$rootKey}[{$i}][{$j}]' must be type of '{$expectedType}'," .
                                    " '{$receivedType}' received.",
                                );
                            }
                        }
                    }
                }
            } elseif ($isArray) {
                if (!is_array($target[$realKey])) {
                    throw new ExpectationFailedException("Field '{$rootKey}' is not an array.");
                }

                foreach ($target[$realKey] as $i => $iValue) {
                    $index = (string) $i;
                    if (($iValue ?? null) === null) {
                        throw new ExpectationFailedException(
                            "Field '{$rootKey}[{$index}]' is missing.",
                        );
                    }

                    if (is_array($value)) {
                        self::assertJsonTypeInternal(
                            $value,
                            $target[$realKey][$index],
                            "{$rootKey}[{$index}]",
                        );
                    } else {
                        $expectedTypes = explode('|', $value);
                        $receivedType = gettype($iValue);
                        $found = false;
                        foreach ($expectedTypes as $expectedType) {
                            if ($expectedType === $receivedType) {
                                $found = true;

                                break;
                            }

                            if ($expectedType === 'double' && $receivedType === 'integer') {
                                $found = true;

                                break;
                            }
                        }

                        if (!$found) {
                            $expectedType = $value;

                            throw new ExpectationFailedException("
                                Field '{$rootKey}[{$i}]' must be type of '{$expectedType}', '{$receivedType}' received.
                            ");
                        }
                    }
                }
            } elseif (is_array($value)) {
                self::assertJsonTypeInternal($value, $target[$realKey], $rootKey);
            } else {
                $expectedTypes = explode('|', $value);
                $receivedType = gettype($target[$realKey]);
                $found = false;
                foreach ($expectedTypes as $expectedType) {
                    if ($expectedType === $receivedType) {
                        $found = true;

                        break;
                    }

                    if ($expectedType === 'double' && $receivedType === 'integer') {
                        $found = true;

                        break;
                    }
                }

                if (!$found) {
                    $expectedType = $value;

                    throw new ExpectationFailedException("
                        Field '{$rootKey}' must be type of '{$expectedType}', '{$receivedType}' received.
                    ");
                }
            }
        }

        foreach (array_keys($target) as $key) {
            if (!in_array($key, $expectedKeys, true)) {
                throw new ExpectationFailedException("Field '{$root}{$key}' is not expected.");
            }
        }
    }

    public function getTestName(): string
    {
        $key = '';
        $traces = debug_backtrace();
        foreach ($traces as $trace) {
            if (strncmp($trace['function'], 'test', 4) === 0) {
                $key = ($trace['class'] ?? '') . '::' . $trace['function'];

                break;
            }
        }

        if ($key === '') {
            throw new NektriaException('Test function starting with "test" has not been found.');
        }

        return $key;
    }

    protected function init(): void
    {
        if (!self::$booted) {
            $this->client = self::createClient();

            /** @var TestRunnerListener $runnerListener */
            $runnerListener = self::getContainer()->get(TestRunnerListener::class);

            if (!self::$onBootExecuted) {
                $runnerListener->onBoot();

                $methods = get_class_methods($this);
                foreach ($methods as $method) {
                    if (str_starts_with($method, 'init')) {
                        // @phpstan-ignore-next-line
                        $this->$method();
                    }
                }
            }

            // self::$onBootExecuted = true;
        }
    }

    protected function setUp(): void
    {
        $this->init();
    }
}