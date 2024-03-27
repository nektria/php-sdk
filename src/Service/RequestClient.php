<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Exception\NektriaException;
use Nektria\Util\JsonUtil;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class RequestClient
{
    public function __construct(
        private readonly HttpClientInterface $client
    ) {
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @return mixed[]
     */
    private function request(
        string $method,
        string $url,
        array $data,
        array $headers
    ): array {
        $body = JsonUtil::encode($data);
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent' => 'Nektria/1.0',
        ], $headers);

        try {
            if ($method === 'GET') {
                $response = $this->client->request(
                    $method,
                    $url,
                    [
                        'headers' => $headers
                    ]
                );
            } elseif ($body === '[]') {
                $response = $this->client->request(
                    $method,
                    $url,
                    [
                        'headers' => $headers
                    ]
                );
            } else {
                $response = $this->client->request(
                    $method,
                    $url,
                    [
                        'headers' => $headers,
                        'body' => $body,
                    ]
                );
            }

            $content = $response->getContent(false);
            $status = $response->getStatusCode();

            if ($status === Response::HTTP_NO_CONTENT) {
                $parsedContent = [];
            } else {
                $parsedContent = JsonUtil::decode($content);
            }
        } catch (Throwable $e) {
            throw NektriaException::new($e);
        }

        if ($status >= 300) {
            throw new NektriaException($content, $status);
        }

        return $parsedContent;
    }

    /**
     * @param array<string, string> $headers
     * @return mixed[]
     */
    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, [], $headers);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @return mixed[]
     */
    public function put(string $url, array $data, array $headers): array
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * @param array<string, string> $headers
     * @return mixed[]
     */
    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, [], $headers);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @return mixed[]
     */
    public function patch(string $url, array $data, array $headers): array
    {
        return $this->request('PATCH', $url, $data, $headers);
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     * @return mixed[]
     */
    public function post(string $url, array $data, array $headers): array
    {
        return $this->request('POST', $url, $data, $headers);
    }
}
