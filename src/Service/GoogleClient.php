<?php

declare(strict_types=1);

namespace Nektria\Service;

use Firebase\JWT\JWT;
use Nektria\Dto\RequestResponse;
use Nektria\Exception\NektriaException;
use Nektria\Util\JsonUtil;

readonly class GoogleClient
{
    public const string TOKEN_HASH = 'sdk_google_token';

    public const int TTL = 3600 * 24 * 7;

    public function __construct(
        private RequestClient $requestClient,
        private SharedVariableCache $sharedVariableCache,
        private string $googleCredentialsFile,
    ) {
    }

    public function token(): string
    {
        if ($this->googleCredentialsFile === 'none') {
            throw new NektriaException('Google is not configured.');
        }

        if ($this->sharedVariableCache->hasKey(self::TOKEN_HASH)) {
            return $this->sharedVariableCache->readString(self::TOKEN_HASH);
        }

        $ttl = 3600 * 24 * 7;
        $p12 = JsonUtil::file($this->googleCredentialsFile);

        $now = time();
        $payload = [
            'iss' => $p12['client_email'],
            'scope' => 'https://www.googleapis.com/auth/devstorage.read_write',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        $signedJwt = JWT::encode(
            $payload,
            $p12['private_key'],
            'RS256'
        );

        $authData = $this->post(
            'https://oauth2.googleapis.com/token',
            data: [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $signedJwt,
            ],
            headers: [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        );

        $token = $authData->json()['access_token'];

        $this->sharedVariableCache->saveString(self::TOKEN_HASH, $token, self::TTL);

        return $token;
    }

    /**
     * @param mixed[] $data
     * @param array<string, string> $headers
     */
    protected function post(
        string $url,
        array $data = [],
        array $headers = [],
    ): RequestResponse {
        return $this->requestClient->post(
            $url,
            data: $data,
            headers: $headers,
            sendBodyAsObject: true
        );
    }
}
