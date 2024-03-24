<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Nektria\Exception\NektriaException;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Throwable;

use function gettype;
use function in_array;
use function is_array;

abstract class TestCase extends WebTestCase
{
    protected const WAREHOUSE_ID = '2026351c-0749-41a6-b433-40beca77e83e';

    protected const TENANT_ID = '91ce10e1-f530-4ce6-8e62-63b87205ab29';

    protected KernelBrowser $client;

    protected Connection $dbConnection;

    protected function setUp(): void
    {
        ini_set('memory_limit', '-1');

        if (!self::$booted) {
            $this->client = self::createClient();
        }

        try {
            $this->dbConnection = self::getContainer()->get('doctrine.orm.entity_manager')->getConnection();
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        $this->init();
    }

    abstract function init();

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
            'list[]' => $expected
        ], $target, '');
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
                            "Field '{$rootKey}[{$index}]' is missing."
                        );
                    }

                    foreach ($target[$realKey][$i] as $j => $jValue) {
                        $jIndex = (string) $j;
                        if (($jValue ?? null) === null) {
                            throw new ExpectationFailedException(
                                "Field '{$rootKey}[{$index}][{$jIndex}]' is missing."
                            );
                        }

                        if (is_array($jValue)) {
                            self::assertJsonTypeInternal(
                                $value,
                                $target[$realKey][$index][$jIndex],
                                "{$rootKey}[{$index}][{$jIndex}]"
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
                                    " '{$receivedType}' received."
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
                            "Field '{$rootKey}[{$index}]' is missing."
                        );
                    }

                    if (is_array($value)) {
                        self::assertJsonTypeInternal(
                            $value,
                            $target[$realKey][$index],
                            "{$rootKey}[{$index}]"
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
}
