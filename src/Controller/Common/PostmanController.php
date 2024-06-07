<?php

declare(strict_types=1);

namespace Nektria\Controller\Common;

use Nektria\Controller\Controller;
use Nektria\Dto\Clock;
use Nektria\Service\ContextService;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use function count;

readonly class PostmanController extends Controller
{
    #[Route('/postman', methods: ['GET'])]
    public function postman(ContextService $contextService): BinaryFileResponse
    {
        $file = "{$contextService->project()}.json";
        $body = $this->buildPostmanCollection($contextService);

        FileUtil::write($file, JsonUtil::encode($body));

        $response = new BinaryFileResponse("/tmp/{$file}");
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file);

        return $response;
    }

    /**
     * @return mixed[]
     */
    private function buildPostmanCollection(ContextService $contextService): array
    {
        $postmanId = match ($contextService->project()) {
            'yieldmanager' => 'd4f9b932-ff22-4b71-985c-5034d0f879ed',
            'routemanager' => '230de982-7d00-423d-b146-166fa019fcaf',
            'compass' => '068a161d-eacb-4922-ad59-cfc33f57606a',
            'metrics' => '493fdd90-bd82-466f-b4f1-23e2c65abdf7',
            'proxy-fontvella' => 'afff0b97-a25e-4e7e-b5c0-41b6486df761',
            'proxy-carrefour' => '58e7b81f-aecc-492c-a440-7b25957443dd',
            'proxy-dia' => '237cb057-d27b-4ed0-b458-3e5d8e237587',
            default => 'bd5c586b-4b1c-47be-a867-d9927e39177c',
        };

        $host = match ($contextService->project()) {
            'yieldmanager' => '{{host_ym}}',
            'routemanager' => '{{host_rm}}',
            'compass' => '{{host_compass}}',
            'metrics' => '{{host_metrics}}',
            'proxy-fontvella', 'proxy-carrefour', 'proxy-dia' => '{{host_proxy}}',
            default => '',
        };

        $command = new Process(array_merge(['../bin/console', 'debug:router', '--format=json']));
        $command->run();

        $data = JsonUtil::decode($command->getOutput());

        /**
         * @var array<string, array{
         *     name: string,
         *     item: array{
         *         name: string,
         *         item: array{
         *             name: string,
         *             request: array{
         *                 description: string,
         *                 method: string,
         *                 url: array{
         *                    raw: string,
         *                 },
         *             }
         *         }[]
         *     }
         * }> $bcs
         */
        $bcs = [];

        /**
         * @var array<string, array{
         *      name: string,
         *      item: array{
         *          name: string,
         *          request: array{
         *              description: string,
         *              method: string,
         *              url: array{
         *                  raw: string,
         *                  path: string[],
         *                  raw: string,
         *              },
         *          }
         *      }
         *  }> $ctrls
         */
        $ctrls = [];

        /**
         * @var array{
         *     name: string,
         *     item: array{
         *         name: string,
         *         item: array{
         *             name: string,
         *             request: array{
         *                 body?: array{
         *                     mode: string,
         *                     raw: string,
         *                     options: array{
         *                         raw: array{
         *                             language: string
         *                         }
         *                     }
         *                 },
         *                 description: string,
         *                 method: string,
         *                 url: array{
         *                    host: string[],
         *                    path: string[],
         *                    raw: string,
         *                    query?: array{
         *                        key: string,
         *                        value: string
         *                    }[]
         *                 },
         *             }
         *         }[]
         *     }[]
         * } $items
         */
        $items = [];

        foreach ($data as $key => $item) {
            if ($contextService->isStaging() || $contextService->isProd()) {
                if (!str_starts_with($key, 'app_api2_')) {
                    continue;
                }

                if (str_contains($key, 'hidden')) {
                    continue;
                }
            }

            if (
                $contextService->isQA()
                && !str_starts_with($key, 'app_web_')
                && !str_starts_with($key, 'app_common_')
            ) {
                continue;
            }

            if (
                !str_starts_with($item['defaults']['_controller'], 'App')
                && !str_starts_with($item['defaults']['_controller'], 'Nektria')
            ) {
                continue;
            }

            $suf = '';
            $folder3 = '';
            if (str_starts_with($item['defaults']['_controller'], 'Nektria')) {
                $suf = ' SDK';
                $folder3 = 'sdk';
            }

            $parts = explode('\\', $item['defaults']['_controller']);
            $folder1 = $parts[2];
            $folder2 = explode('Controller', $parts[3])[0];

            if (!isset($bcs[$folder1])) {
                $bcs[$folder1] = [
                    'name' => $folder1,
                    'item' => [],
                ];
            }

            if (!isset($ctrls["{$folder1}_{$folder2}_{$folder3}"])) {
                $ctrls["{$folder1}_{$folder2}_{$folder3}"] = [
                    'name' => "{$folder2}{$suf}",
                    'item' => [],
                ];
            }

            $ctrls["{$folder1}_{$folder2}_{$folder3}"]['item'][] = $this->buildPostmanRequest(
                $key,
                $host,
                $item,
                $contextService,
            );
        }

        if ($contextService->isLocalEnvironament()) {
            $data = [];

            try {
                $command = new Process(array_merge(['../bin/console', 'list', '--format=json']));
                $command->run();

                $data = JsonUtil::decode($command->getOutput())['commands'];
            } catch (Throwable $e) {
                $command = new Process(array_merge(['../bin/console', 'list', '--raw']));
                $command->run();

                $lines = explode("\n", $command->getOutput());
                foreach ($lines as $line) {
                    $name = explode(' ', $line)[0];
                    $data[] = [
                        'name' => $name,
                        'definition' => [
                            'arguments' => [],
                        ],
                    ];
                }
            }

            $adminConsole = [];
            $sdkConsole = [];
            $consoleItems = [];
            foreach ($data as $item) {
                if (
                    !str_starts_with($item['name'], 'admin:')
                    && !str_starts_with($item['name'], 'sdk:')
                ) {
                    continue;
                }

                $args = [];
                foreach ($item['definition']['arguments'] as $arg) {
                    if ($arg['is_required'] === true) {
                        $args[] = $arg['name'];
                    } else {
                        $args[] = "[{$arg['name']}]";
                    }
                }

                $body = [
                    'command' => $item['name'],
                    'args' => $args,
                ];

                $transformed = [
                    'name' => $item['name'],
                    'request' => [
                        'body' => [
                            'mode' => 'raw',
                            'raw' => JsonUtil::encode($body, true),
                            'options' => [
                                'raw' => [
                                    'language' => 'json',
                                ],
                            ],
                        ],
                        'description' => $item['name'],
                        'method' => 'PATCH',
                        'url' => [
                            'raw' => "{$host}/api/admin/tools/console",
                            'host' => [$host],
                            'path' => ['api/admin/tools/console'],
                        ],
                    ],
                ];

                if (str_starts_with($item['name'], 'admin:')) {
                    $adminConsole[] = $transformed;
                } else {
                    $sdkConsole[] = $transformed;
                }
            }

            if (count($adminConsole) > 0) {
                $consoleItems[] = [
                    'name' => 'Admin',
                    'item' => $adminConsole,
                ];
            }

            if (count($sdkConsole) > 0) {
                $consoleItems[] = [
                    'name' => 'SDK',
                    'item' => $sdkConsole,
                ];
            }

            $bcs['Console'] = [
                'name' => 'Console',
                'item' => $consoleItems,
            ];
        }

        foreach ($ctrls as $key => $ctrl) {
            $f1 = explode('_', $key)[0];
            $bcs[$f1]['item'][] = $ctrl;
        }

        foreach ($bcs as $bc) {
            $items[] = $bc;
        }

        return [
            'info' => [
                '_exporter_id' => '4175778',
                '_postman_id' => $postmanId,
                'name' => "NK {$contextService->project()}",
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $items,
            'event' => [
                [
                    'listen' => 'prerequest',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => [
                            "let app = '';",
                            "if (pm.request.url.host[0] === '{{host_ym}}') {",
                            "    app = 'yieldmanager';",
                            "} if (pm.request.url.host[0] === '{{host_rm}}') {",
                            "    app = 'routemanager';",
                            "} if (pm.request.url.host[0] === '{{host_proxy}}') {",
                            "    app = 'nektria-proxy';",
                            "} if (pm.request.url.host[0] === '{{host_compass}}') {",
                            "    app = 'compass';",
                            "} if (pm.request.url.host[0] === '{{host_metrics}}') {",
                            "    app = 'metrics';",
                            '} else {',
                            '    console.log(pm.request.url.host[0])',
                            '}',
                            "if (pm.request.url.path.join('/').indexOf('/admin') > 0) {",
                            "    pm.request.addHeader({key: 'X-Api-Id', value: '{{api_key_admin}}'})",
                            '} else {',
                            "    pm.request.addHeader({key: 'X-Api-Id', value: '{{api_key}}'})",
                            '}',
                            '',
                            "pm.request.addHeader({key: 'X-Nektria-App', value: app});",
                            "pm.request.addHeader({key: 'X-Origin', value: 'Nektria/Postman'});",
                            "pm.request.addHeader({key: 'Content-type', value: 'application/json'});",
                            "pm.request.addHeader({key: 'X-Trace', value: '00000000-0000-4000-8000-000000000000'});",
                            '',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array{
     *     path: string,
     *     method: string,
     *     defaults: array{
     *         _controller: string,
     *     }
     * } $data
     *
     * @return array{
     *     name: string,
     *     request: array{
     *         body?: array{
     *             mode: string,
     *             raw: string,
     *             options: array{
     *                 raw: array{
     *                     language: string
     *                 }
     *             }
     *         },
     *         description: string,
     *         method: string,
     *         url: array{
     *            raw: string,
     *            host: string[],
     *            path: string[],
     *            query?: array{
     *                key: string,
     *                value: string
     *            }[]
     *         },
     *     }
     * }
     */
    private function buildPostmanRequest(string $key, string $host, array $data, ContextService $contextService): array
    {
        $public = $contextService->isStaging() || $contextService->isProd();
        $description = $public ? $key : "{$key} ({$data['defaults']['_controller']})";
        $path = $data['path'];
        $path = str_replace(['{', '}'], ['{{', '}}'], $path);

        $pathArgs = [];

        $pathParts = explode('{{', $path);

        foreach ($pathParts as $part) {
            if (!str_contains($part, '}}')) {
                continue;
            }

            $part = explode('}}', $part)[0];
            $pathArgs[] = "pm.environment.set('{$part}', 'value');";
        }

        $url = "{$host}{$path}";
        $path = substr($path, 1);
        $method = explode('|', $data['method'])[0];
        $name = explode('::', $data['defaults']['_controller'])[1];

        $fixedName = '';
        foreach (str_split($name) as $letter) {
            if ($letter >= 'A' && $letter <= 'Z' && $fixedName !== '') {
                $fixedName .= ' ';
            }
            $fixedName .= $letter;
        }
        $fixedName = ucwords($fixedName);

        $file = str_replace('\\', '/', explode('::', $data['defaults']['_controller'])[0]) . '.php';
        $file = str_replace(['App', 'Nektria/'], ['/app/src', '/app/vendor/nektria/php-sdk/src/'], $file);

        $json = $this->buildPostmanRequestBody($file, $name);

        if ($json !== null && $method === 'GET') {
            $query = [];
            foreach ($json as $k => $value) {
                if ($value === true) {
                    $value = 'true';
                } elseif ($value === false) {
                    $value = 'false';
                } elseif ($value === null) {
                    $value = 'null';
                }

                $query[] = [
                    'key' => $k,
                    'value' => (string) $value,
                ];
            }

            return [
                'name' => $fixedName,
                'request' => [
                    'description' => $description,
                    'method' => $method,
                    'url' => [
                        'raw' => $url,
                        'host' => [$host],
                        'path' => [$path],
                        'query' => $query,
                    ],
                ],
                'event' => [
                    [
                        'listen' => 'prerequest',
                        'script' => [
                            'type' => 'text/javascript',
                            'exec' => $pathArgs,
                        ],
                    ],
                ],
            ];
        }

        if ($json !== null) {
            return [
                'name' => $fixedName,
                'request' => [
                    'body' => [
                        'mode' => 'raw',
                        'raw' => JsonUtil::encode($json, true),
                        'options' => [
                            'raw' => [
                                'language' => 'json',
                            ],
                        ],
                    ],
                    'description' => $description,
                    'method' => $method,
                    'url' => [
                        'raw' => $url,
                        'host' => [$host],
                        'path' => [$path],
                    ],
                ],
                'event' => [
                    [
                        'listen' => 'prerequest',
                        'script' => [
                            'type' => 'text/javascript',
                            'exec' => $pathArgs,
                        ],
                    ],
                ],
            ];
        }

        return [
            'name' => $fixedName,
            'request' => [
                'description' => $description,
                'method' => $method,
                'url' => [
                    'raw' => $url,
                    'host' => [$host],
                    'path' => [$path],
                ],
            ],
            'event' => [
                [
                    'listen' => 'prerequest',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => $pathArgs,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return mixed[]|null
     */
    private function buildPostmanRequestBody(string $file, string $method): ?array
    {
        $content = FileUtil::read($file);
        $fileLines = explode("\n", $content);
        $lines = [];
        $inside = false;
        foreach ($fileLines as $line) {
            if (str_starts_with($line, "    public function {$method}")) {
                $inside = true;
            }

            if ($inside) {
                $lines[] = $line;

                if ($line === '    }') {
                    break;
                }
            }
        }

        $found = false;
        $body = [];

        $hash = [];

        $previousLine = '';

        $defaultNow = Clock::now();
        $defaultStartTime = $defaultNow->setTime(16);
        $defaultEndTime = $defaultNow->setTime(18);

        foreach ($lines as $line) {
            $line = $previousLine . StringUtil::trim($line);
            $previousLine = '';
            if (
                !str_ends_with($line, ',')
                && !str_ends_with($line, ';')
                && !str_ends_with($line, '}')
                && !str_ends_with($line, '{')
            ) {
                $previousLine = $line;

                continue;
            }

            if (!str_contains($line, '->requestData->')) {
                continue;
            }

            if (str_contains($line, '->requestData->hasField')) {
                continue;
            }

            $matches = [];
            if (str_contains($line, '->requestData->retrieve')) {
                $pattern = '/requestData->retrieve(\w+)\((\'|")([^\'"]+)\2/';
            } else {
                $pattern = '/requestData->get(\w+)\((\'|")([^\'"]+)\2/';
            }
            preg_match($pattern, $line, $matches);

            if (count($matches) === 0) {
                throw new RuntimeException();
            }
            $found = true;
            $type = $matches[1];
            $name = $matches[3];

            $sample = match ($type) {
                'Address' => [
                    'addressLine1' => 'string',
                    'addressLine2' => 'string',
                    'city' => 'string',
                    'countryCode' => 'string',
                    'elevator' => true,
                    'latitude' => 0.1,
                    'longitude' => 0.2,
                    'postalCode' => 'string',
                ],
                'Array' => [],
                'Bool' => true,
                'Clock', 'ClockAsLocal' => $defaultNow->dateTimeString(),
                'ClockTz' => $defaultNow->iso8601String(),
                'Date' => $defaultNow->dateString(),
                'Float' => 1.2,
                'Id' => '9baff5ad-db09-4fc0-b876-948b44e4158a',
                'IdsCSV' => 'ID_1,ID_2,...,ID_N',
                'Int' => 1,
                'Length' => [],
                'String' => 'string',
                'StringArray' => ['string'],
                default => '?',
            };

            if (str_contains($name, 'startTime')) {
                $sample = $defaultStartTime->dateTimeString();
            } elseif (str_contains($name, 'endTime')) {
                $sample = $defaultEndTime->dateTimeString();
            }

            if ($sample === '?') {
                throw new RuntimeException($type);
            }

            $parts = array_reverse(explode('.', $name));
            $partsLen = count($parts);

            foreach ($parts as $i => $iValue) {
                $part = $iValue;
                if ($i === ($partsLen - 1)) {
                    $body[$part] = $sample;

                    break;
                }

                if (str_contains($part, '$')) {
                    $sample = [$sample];
                } else {
                    $remain = '';
                    for ($j = $i + 1; $j < $partsLen; ++$j) {
                        $remain .= $parts[$j];
                    }

                    $mix = [];
                    if ($remain !== '') {
                        $mix = $hash[$remain] ?? [];
                    }

                    $mix[$part] = $sample;
                    $sample = $mix;
                    ksort($sample);

                    $hash[$remain] = $sample;
                }
            }
        }

        if (!$found) {
            return null;
        }

        ksort($body);

        return $body;
    }
}
