<?php

declare(strict_types=1);

namespace Nektria\Service;

use Firebase\JWT\JWT;
use Nektria\Document\FileDocument;
use Nektria\Dto\RequestResponse;
use Nektria\Exception\NektriaException;
use Nektria\Exception\RequestException;
use Nektria\Util\JsonUtil;

readonly class GoogleClient
{
    public const string TOKEN_HASH = 'sdk_google_token';

    public const int TTL = 3600;

    /**
     * @param string[] $googleScopes
     */
    public function __construct(
        private RequestClient $requestClient,
        private VariableCache $variableCache,
        private ContextService $contextService,
        private string $googleCredentialsFile,
        private array $googleScopes,
    ) {
    }

    public function storageDeleteFile(
        string $folder,
        string $filename,
        ?string $project = null
    ): void {
        try {
            $project ??= $this->contextService->project();
            $folder = urlencode("{$project}/{$folder}/");
            $bucket = "nektria-{$this->contextService->env()}";

            $this->delete(
                "https://storage.googleapis.com/storage/v1/b/{$bucket}/o/{$folder}{$filename}",
                headers: [
                    'Content-Type' => 'application/json',
                ]
            );
        } catch (RequestException $e) {
            throw new NektriaException($e->response()->json()['_response']);
        }
    }

    public function storageDeleteFolder(
        string $folder,
        ?string $project = null
    ): void {
        try {
            $project ??= $this->contextService->project();
            $folder = urlencode("{$project}/{$folder}/");
            $bucket = "nektria-{$this->contextService->env()}";

            $this->delete(
                "https://storage.googleapis.com/storage/v1/b/{$bucket}/folders/{$folder}",
                headers: [
                    'Content-Type' => 'application/json',
                ]
            );
        } catch (RequestException $e) {
            throw new NektriaException($e->response()->json()['_response']);
        }
    }

    public function storageDownloadFile(
        string $folder,
        string $filename,
        ?string $project = null
    ): FileDocument {
        try {
            $project ??= $this->contextService->project();
            $folder = urlencode("{$project}/{$folder}/");
            $bucket = "nektria-{$this->contextService->env()}";

            $data = $this->get(
                "https://storage.googleapis.com/storage/v1/b/{$bucket}/o/{$folder}{$filename}",
                data: [
                    'alt' => 'media',
                ],
            );

            $tmpFilename = "/tmp/{$filename}";
            file_put_contents($tmpFilename, $data->body);

            return new FileDocument($tmpFilename);
        } catch (RequestException $e) {
            throw new NektriaException($e->response()->json()['_response']);
        }
    }

    public function storageUploadFile(
        string $folder,
        string $name,
        string $uploadFilename,
        ?string $project = null
    ): void {
        try {
            $project ??= $this->contextService->project();
            $folder = urlencode("{$project}/{$folder}/");
            $bucket = "nektria-{$this->contextService->env()}";

            $this->file(
                "https://storage.googleapis.com/upload/storage/v1/b/{$bucket}/o",
                filename: $uploadFilename,
                data: [
                    'name' => "{$folder}{$name}",
                    'uploadType' => 'media',
                ]
            );
        } catch (RequestException $e) {
            $json = $e->response()->json();

            throw new NektriaException($json['_response'] ?? $json['error']['message']);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    protected function delete(
        string $url,
        array $headers = [],
        bool $retry = true,
    ): RequestResponse {
        $token = $this->token();
        $headers['Authorization'] = "Bearer {$token}";

        try {
            return $this->requestClient->delete(
                $url,
                headers: $headers,
            );
        } catch (RequestException $e) {
            if ($retry && $e->response()->status === 401) {
                return $this->delete(
                    $url,
                    headers: $headers,
                    retry: false,
                );
            }

            throw $e;
        }
    }

    /**
     * @param array<string, string|int|float|bool> $data
     * @param array<string, string> $headers
     */
    protected function file(
        string $url,
        string $filename,
        array $data = [],
        array $headers = [],
        bool $retry = true,
    ): RequestResponse {
        $token = $this->token();
        $headers['Authorization'] = "Bearer {$token}";

        try {
            return $this->requestClient->file(
                $url,
                filename: $filename,
                data: $data,
                headers: $headers,
            );
        } catch (RequestException $e) {
            if ($retry && $e->response()->status === 401) {
                return $this->file(
                    $url,
                    filename: $filename,
                    data: $data,
                    headers: $headers,
                    retry: false,
                );
            }

            throw $e;
        }
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     */
    protected function get(
        string $url,
        array $data = [],
        array $headers = [],
        bool $retry = true,
    ): RequestResponse {
        $token = $this->token();
        $headers['Authorization'] = "Bearer {$token}";

        try {
            return $this->requestClient->get(
                $url,
                data: $data,
                headers: $headers,
            );
        } catch (RequestException $e) {
            if ($retry && $e->response()->status === 401) {
                return $this->get(
                    $url,
                    data: $data,
                    headers: $headers,
                    retry: false,
                );
            }

            throw $e;
        }
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     */
    protected function post(
        string $url,
        array $data = [],
        array $headers = [],
        bool $retry = true,
        bool $authRequest = false,
    ): RequestResponse {
        if (!$authRequest) {
            $token = $this->token();
            $headers['Authorization'] = "Bearer {$token}";
        }

        try {
            return $this->requestClient->post(
                $url,
                data: $data,
                headers: $headers,
                sendBodyAsObject: true
            );
        } catch (RequestException $e) {
            if ($retry && $e->response()->status === 401) {
                return $this->post(
                    $url,
                    data: $data,
                    headers: $headers,
                    retry: false,
                );
            }

            throw $e;
        }
    }

    protected function token(): string
    {
        if ($this->googleCredentialsFile === 'none') {
            throw new NektriaException('Google is not configured.');
        }

        $p12 = JsonUtil::file($this->googleCredentialsFile);

        $now = time();
        $payload = [
            'iss' => $p12['client_email'],
            'scope' => implode(' ', $this->googleScopes),
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + self::TTL,
        ];

        $signedJwt = JWT::encode(
            $payload,
            $p12['private_key'],
            'RS256'
        );

        try {
            $authData = $this->post(
                'https://oauth2.googleapis.com/token',
                data: [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $signedJwt,
                ],
                headers: [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                authRequest: true,
            );
        } catch (RequestException $e) {
            throw new NektriaException($e->response()->json()['error_description']);
        }

        $json = $authData->json();
        if (($json['access_token'] ?? null) === null) {
            throw new NektriaException('Google token not received');
        }

        $token = $json['access_token'];

        $this->variableCache->saveString(self::TOKEN_HASH, $token, self::TTL);

        return $token;
    }
}
