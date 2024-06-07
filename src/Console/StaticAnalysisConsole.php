<?php

declare(strict_types=1);

namespace Nektria\Console;

use Nektria\Exception\NektriaException;
use Nektria\Util\JsonUtil;
use Symfony\Component\Process\Process;

class StaticAnalysisConsole extends Console
{

    public function __construct()
    {
        parent::__construct('sdk:static-analysis');
    }

    protected function play(): void
    {
        $command = new Process(array_merge(['bin/console', 'debug:router', '--format=json']));
        $command->run();

        $data = JsonUtil::decode($command->getOutput());
        $failed = false;
        foreach ($data as $hash => $endpoint) {
            $failed |= $this->analyseEndpoint($hash, $endpoint);
        }

        if ($failed) {
            throw new NektriaException('Some endpoints are not correctly configured');
        }
    }

    /**
     * @param array{
     *     path: string,
     *     pathRegex: string,
     *     host: string,
     *     hostRegex: string,
     *     scheme: string,
     *     method: string,
     *     class: string,
     *     defaults: array{
     *         _controller: string,
     *         _format: string,
     *     },
     *     requirements: array{
     *         code: string,
     *         _locale: string,
     *     },
     *     options: array{
     *         compiler_class: string,
     *         utf8: bool,
     *     },
     * } $endpoint
     * @return bool true if the endpoint is not correctly configured
     */
    private function analyseEndpoint(string $hash, array $endpoint): bool
    {
        if (str_starts_with($endpoint['path'], '/_')) {
            return false;
        }

        $messages = [];

        // Path cannot end with '/'
        if (str_ends_with($endpoint['path'], '/')) {
            $messages[] = 'Path cannot end with "/"';
        }

        // every '{' can only be after '/' in path
        if (str_contains($endpoint['path'], '{')) {
            $parts = explode('/', $endpoint['path']);
            foreach ($parts as $part) {
                $pos = strpos($part, '{');
                if ($pos !== false && $pos !== 0) {
                    $messages[] = 'A variable in path must no be mixed with other characters';
                    break;
                }
            }
        }

        // every '}' must be followed '/' except for the last one
        if (str_contains($endpoint['path'], '}')) {
            $parts = explode('/', $endpoint['path']);
            foreach ($parts as $part) {
                $pos = strpos($part, '}');
                if ($pos !== false && !str_ends_with($part, '}')) {
                    $messages[] = 'A variable in path must no be mixed with other characters';
                    break;
                }
            }
        }

        if ($endpoint['method'] === 'ANY') {
            $messages[] = 'Method must be defined';
        } elseif ($endpoint['method'] === 'GET') {
            $function = explode('::', $endpoint['defaults']['_controller'])[1];
            if (!str_starts_with($function, 'get') && !str_starts_with($function, 'list')) {
                $messages[] = 'Function must start by either "get" or "list"';
            }
        } elseif ($endpoint['method'] === 'PUT') {
            $function = explode('::', $endpoint['defaults']['_controller'])[1];
            if (!str_starts_with($function, 'save')) {
                $messages[] = 'Function must start by "save"';
            }
        } elseif ($endpoint['method'] === 'DELETE') {
            $function = explode('::', $endpoint['defaults']['_controller'])[1];
            if (!str_starts_with($function, 'delete')) {
                $messages[] = 'Function must start by "delete"';
            }
        } elseif ($endpoint['method'] === 'POST' || $endpoint['method'] === 'PATCH') {
            $function = explode('::', $endpoint['defaults']['_controller'])[1];
            if (
                str_starts_with($function, 'delete')
                || str_starts_with($function, 'get')
                || str_starts_with($function, 'list')
                || str_starts_with($function, 'save')
            ) {
                $messages[] = 'Function must not start by "delete", "get", "list" or "save"';
            }
        } else {
            $messages[] = "Method '{$endpoint['method']}' not supported";
        }

        if (empty($messages)) {
            return false;
        }

        $this->output()->writeln("Endpoint {$endpoint['defaults']['_controller']} is not correctly configured");
        foreach ($messages as $message) {
            $this->output()->writeln("    * {$message}");
        }
        $this->output()->writeln('');

        return true;
    }
}
