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
        private readonly LogService $logService,
        private readonly ContextService $contextService,
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
        array $data = [],
        array $headers = [],
        array $options = [],
        bool $sendBodyAsObject = false
    ): RequestResponse {
        $body = JsonUtil::encode($data);
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nektria/1.0',
            'X-Origin' => $this->contextService->project(),
        ], $headers);

        $options['verify_peer'] = false;
        $options['verify_host'] = false;
        $options['headers'] = $headers;

        if ($sendBodyAsObject) {
            $body = $data;
        }

        try {
            if ($method === 'GET') {
                $params = http_build_query($data);
                if ($params !== '') {
                    $url .= "?{$params}";
                }
            } else {
                $options['body'] = $body;
            }

            $response = $this->client->request(
                $method,
                $url,
                $options
            );

            $content = $response->getContent(false);
            $status = $response->getStatusCode();
            $respHeaders = $response->getHeaders(false);

            $response = new RequestResponse(
                $method,
                $url,
                $status,
                $content,
                $headers,
                $respHeaders
            );
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        $this->logService->debug([
            'method' => $response->method,
            'request' => $data,
            'requestHeaders' => $headers,
            'response' => $response->json(),
            'responseHeaders' => $respHeaders,
            'status' => $response->status,
            'url' => $url,
        ], "{$status} {$method} {$url}");

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
     * @param array<string, string|int|bool|float> $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function get(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = []
    ): RequestResponse {
        return $this->request('GET', $url, $data, $headers, $options);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function put(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = [],
        bool $sendBodyAsObject = false
    ): RequestResponse {
        return $this->request('PUT', $url, $data, $headers, $options, $sendBodyAsObject);
    }

    /**
     * @param array<string, string|int|bool|float> $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function delete(
        string $url,
        array $data = [],
        array $headers = [],
        array $options = []
    ): RequestResponse {
        return $this->request('DELETE', $url, $data, $headers, $options);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string|bool|number> $options
     */
    public function patch(
        string $url,
        array $data = [],
        array $headers = [],
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
        array $data = [],
        array $headers = [],
        array $options = [],
        bool $sendBodyAsObject = false
    ): RequestResponse {
        return $this->request('POST', $url, $data, $headers, $options, $sendBodyAsObject);
    }
}
