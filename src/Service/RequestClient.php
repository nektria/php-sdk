<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Dto\RequestResponse;
use Nektria\Exception\NektriaException;
use Nektria\Exception\RequestException;
use Nektria\Util\JsonUtil;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class RequestClient
{
    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LogService $logService
    ) {
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    private function request(
        string $method,
        string $url,
        array $data,
        array $headers,
        array $options = [],
        bool $sendBodyAsObject = false
    ): RequestResponse {
        $body = JsonUtil::encode($data);
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nektria/1.0',
        ], $headers);

        $options['verify_peer'] = false;
        $options['verify_host'] = false;
        $options['headers'] = $headers;

        if ($sendBodyAsObject) {
            $body = $data;
        }

        try {
            if ($method === 'GET') {
                $response = $this->client->request(
                    $method,
                    $url,
                    $options
                );
            } elseif ($body === '[]') {
                $response = $this->client->request(
                    $method,
                    $url,
                    $options
                );
            } else {
                $options['body'] = $body;
                $response = $this->client->request(
                    $method,
                    $url,
                    $options
                );
            }

            $content = $response->getContent(false);
            $status = $response->getStatusCode();

            $response = new RequestResponse($method, $url, $status, $content);
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        if ($status >= 300) {
            $errorContent = $content;

            try {
                $errorContent = JsonUtil::decode($content);
            } catch (Throwable) {
            }

            $this->logService->error([
                'method' => $method,
                'request' => $data,
                'response' => $errorContent,
                'status' => $status,
                'url' => $url,
            ], "{$method} {$url} failed with status {$status}");

            throw new RequestException($response);
        }

        return $response;
    }

    /**
     * @param array<string, string|int|bool|float> $body
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     *
     */
    public function get(string $url, array $body, array $headers, array $options = []): RequestResponse
    {
        return $this->request('GET', $url, $body, $headers, $options);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function put(
        string $url,
        array $data,
        array $headers,
        array $options = [],
        bool $sendBodyAsObject = false
    ): RequestResponse {
        return $this->request('PUT', $url, $data, $headers, $options, $sendBodyAsObject);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function delete(string $url, array $headers = [], array $options = []): RequestResponse
    {
        return $this->request('DELETE', $url, [], $headers, $options);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function patch(
        string $url,
        array $data,
        array $headers,
        array $options = [],
        bool $sendBodyAsObject = false
    ): RequestResponse {
        return $this->request('PATCH', $url, $data, $headers, $options, $sendBodyAsObject);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function post(
        string $url,
        array $data,
        array $headers,
        array $options = [],
        bool $sendBodyAsObject = false
    ): RequestResponse {
        return $this->request('POST', $url, $data, $headers, $options, $sendBodyAsObject);
    }
}
