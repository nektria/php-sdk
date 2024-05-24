<?php

declare(strict_types=1);

namespace Nektria\Controller\Common;

use Nektria\Controller\Controller;
use Nektria\Service\ContextService;
use Nektria\Util\FileUtil;
use Nektria\Util\JsonUtil;
use Nektria\Util\StringUtil;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Attribute\Route;

use function count;

class PostmanController extends Controller
{
    #[Route('/postman', methods: ['GET'])]
    public function postman(ContextService $contextService): BinaryFileResponse
    {
        $file = "{$contextService->project()}.json";
        $body = $this->buildPostmanCollection($contextService, false);

        FileUtil::write("/tmp/{$file}", JsonUtil::encode($body));

        $response = new BinaryFileResponse("/tmp/{$file}");
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file);

        return $response;
    }

    /**
     * @return mixed[]
     */
    private function buildPostmanCollection(ContextService $contextService, bool $public): array
    {
        $postmanId = match ($contextService->project()) {
            'yieldmanager' => 'd4f9b932-ff22-4b71-985c-5034d0f879ed',
            'routemanager' => '230de982-7d00-423d-b146-166fa019fcaf',
            'compass' => '068a161d-eacb-4922-ad59-cfc33f57606a',
            'metrics' => '493fdd90-bd82-466f-b4f1-23e2c65abdf7',
            'fontvella-proxy' => 'afff0b97-a25e-4e7e-b5c0-41b6486df761',
            'carrefour-proxy' => '58e7b81f-aecc-492c-a440-7b25957443dd',
            'dia-proxy' => '237cb057-d27b-4ed0-b458-3e5d8e237587',
            default => 'bd5c586b-4b1c-47be-a867-d9927e39177c',
        };

        $host = match ($contextService->project()) {
            'yieldmanager' => '{{host_ym}}',
            'routemanager' => '{{host_rm}}',
            'compass' => '{{host_compass}}',
            'metrics' => '{{host_metrics}}',
            'fontvella-proxy', 'carrefour-proxy', 'dia-proxy' => '{{host_proxy}}',
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
            if ($public && !str_starts_with($key, 'app_api2_')) {
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
                false
            );
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
                            ''
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
    private function buildPostmanRequest(string $key, string $host, array $data, bool $public): array
    {
        $description = $public ? $key : "{$key} ({$data['defaults']['_controller']})";
        $url = "{$host}{$data['path']}";
        $path = substr($data['path'], 1);
        $method = explode('|', $data['method'])[0];
        $name = explode('::', $data['defaults']['_controller'])[1];

        $file = str_replace('\\', '/', explode('::', $data['defaults']['_controller'])[0]) . '.php';
        $file = str_replace(['App', 'Nektria/'], ['/app/src', '/app/vendor/nektria/php-sdk/src/'], $file);

        $json = $this->buildPostmanRequestBody($file, $name);

        if ($json !== null && $method === 'GET') {
            $query = [];
            foreach ($json as $k => $value) {
                $query[] = [
                    'key' => $k,
                    'value' => $value,
                ];
            }

            return [
                'name' => $name,
                'request' => [
                    'description' => $description,
                    'method' => $method,
                    'url' => [
                        'raw' => $url,
                        'host' => [$host],
                        'path' => [$path],
                        'query' => $query
                    ],
                ]
            ];
        }

        if ($json !== null) {
            return [
                'name' => $name,
                'request' => [
                    'body' => [
                        'mode' => 'raw',
                        'raw' => JsonUtil::encode($json, true),
                        'options' => [
                            'raw' => [
                                'language' => 'json'
                            ]
                        ]
                    ],
                    'description' => $description,
                    'method' => $method,
                    'url' => [
                        'raw' => $url,
                        'host' => [$host],
                        'path' => [$path],
                    ],
                ]
            ];
        }

        return [
            'name' => $name,
            'request' => [
                'description' => $description,
                'method' => $method,
                'url' => [
                    'raw' => $url,
                    'host' => [$host],
                    'path' => [$path],
                ],
            ]
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

            if ($matches === []) {
                throw new RuntimeException();
            }
            $found = true;
            $type = $matches[1];
            $name = $matches[3];

            $sample = match ($type) {
                'Array' => [],
                'Address' => [
                    'addressLine1' => 'string',
                    'addressLine2' => 'string',
                    'city' => 'string',
                    'countryCode' => 'string',
                    'elevator' => true,
                    'latitude' => 0.0,
                    'longitude' => 0.0,
                    'postalCode' => 'string',
                ],
                'Clock', 'ClockAsLocal' => '2024-12-31T23:59:59',
                'ClockTz' => '2024-12-31T23:59:59+00:00',
                'Id' => '9baff5ad-db09-4fc0-b876-948b44e4158a',
                'Date' => '2024-12-31',
                'String' => 'string',
                'StringArray' => ['string'],
                'Int' => 1,
                'Float' => 1.2,
                'Bool' => true,
                'Length' => [],
                default => '?',
            };

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

                    $hash[$remain] = $sample;
                }
            }
        }

        if (!$found) {
            return null;
        }

        return $body;
    }
}
