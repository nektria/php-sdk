<?php

declare(strict_types=1);

namespace Nektria\Service;

use DomainException;
use Nektria\Dto\RequestResponse;
use Nektria\Exception\NektriaException;
use Nektria\Util\JsonUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class RequestClient
{
    private ?RequestResponse $response;

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LogService $logService
    ) {
        $this->response = null;
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string> $options
     */
    private function request(
        string $method,
        string $url,
        array $data,
        array $headers,
        array $options = []
    ): mixed {
        $this->response = null;
        $body = JsonUtil::encode($data);
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nektria/1.0',
        ], $headers);

        $options['verify_peer'] = false;
        $options['verify_host'] = false;
        $options['headers'] = $headers;

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

            $this->response = new RequestResponse($method, $url, $status, $content);

            if ($status === Response::HTTP_NO_CONTENT || $content === '') {
                $parsedContent = [];
            } else {
                $parsedContent = JsonUtil::decode($content);
            }
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

            throw new NektriaException($content, $status);
        }

        return $parsedContent;
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $options
     */
    public function get(string $url, array $headers = [], array $options = []): mixed
    {
        return $this->request('GET', $url, [], $headers, $options);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string> $options
     * @return mixed[]
     */
    public function put(string $url, array $data, array $headers, array $options = []): array
    {
        return $this->request('PUT', $url, $data, $headers, $options);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, string> $options
     * @return mixed[]
     */
    public function delete(string $url, array $headers = [], array $options = []): array
    {
        return $this->request('DELETE', $url, [], $headers, $options);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string> $options
     * @return mixed[]
     */
    public function patch(string $url, array $data, array $headers, array $options = []): array
    {
        return $this->request('PATCH', $url, $data, $headers, $options);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @param array<string, string> $options
     * @return mixed[]
     */
    public function post(string $url, array $data, array $headers, array $options = []): array
    {
        return $this->request('POST', $url, $data, $headers, $options);
    }

    public function lastResponse(): RequestResponse
    {
        if ($this->response === null) {
            throw new DomainException('No response available.');
        }

        return $this->response;
    }
}
