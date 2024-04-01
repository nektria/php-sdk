<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\ThrowableDocument;
use Nektria\Util\JsonUtil;
use Throwable;

use function in_array;
use function strlen;

class AlertService
{
    private string $token;

    public function __construct(
        private readonly string $alertsToken,
        private readonly ContextService $contextService,
        private readonly RequestClient $requestClient,
    ) {
        $this->token = $this->alertsToken;
    }

    /**
     * @param array{
     *     content?: string,
     *     embeds?: array<array{
     *         title?: string,
     *         type?: string,
     *         description?: string,
     *         url?: string,
     *         timestamp?: string,
     *         color?: int,
     *         footer?: array{
     *             text?: string,
     *             icon_url?: string
     *         },
     *         image?: array{
     *             url?: string,
     *             height?: int,
     *             width?: int
     *         },
     *         thumbnail?: array{
     *             url?: string,
     *             height?: int,
     *             width?: int
     *         },
     *         author?: array{
     *             name?: string,
     *         },
     *         fields?: array<array{
     *             name?: string,
     *             value?: string,
     *             inline?: bool
     *         }>,
     *     }>,
     *     components?: array<array{
     *         type: int,
     *         components: array<array{
     *             type: int,
     *             style: int,
     *             label: string,
     *             custom_id: string
     *         }>
     *     }>
     * } $message
     */
    private function makeRequest(string $channel, array $message): void
    {
        if ($this->contextService->env() === 'test') {
            return;
        }

        $channel = $this->parseChannel($channel);
        $this->requestClient->post(
            "https://discord.com/api/channels/{$channel}/messages",
            $message,
            [
                'Authorization' => "Bot {$this->token}"
            ]
        );
    }

    /**
     * @param array{
     *     content?: string,
     *     embeds?: array<array{
     *         title?: string,
     *         type?: string,
     *         description?: string,
     *         url?: string,
     *         timestamp?: string,
     *         color?: int,
     *         footer?: array{
     *             text?: string,
     *             icon_url?: string
     *         },
     *         image?: array{
     *             url?: string,
     *             height?: int,
     *             width?: int
     *         },
     *         thumbnail?: array{
     *             url?: string,
     *             height?: int,
     *             width?: int
     *         },
     *         author?: array{
     *             name?: string,
     *         },
     *         fields?: array<array{
     *             name?: string,
     *             value?: string,
     *             inline?: bool
     *         }>,
     *     }>,
     *     components?: array<array{
     *         type: int,
     *         components: array<array{
     *             type: int,
     *             style: int,
     *             label: string,
     *             custom_id: string
     *         }>
     *     }>
     * } $message
     */
    public function sendMessage(string $channel, array $message): void
    {
        try {
            $this->makeRequest($channel, $message);
        } catch (Throwable $e) {
            try {
                $content = "‎\n" .
                    '**Discord Api Error**' .
                    "```json\n" .
                    JsonUtil::encode(JsonUtil::decode($e->getMessage()), true) .
                    "\n```" .
                    "Trace: {$this->contextService->traceId()}\n" .
                    "‎\n‎";

                if (str_contains($content, 'You are being rate limited.')) {
                    return;
                }

                $this->makeRequest($channel, [
                    'content' => $content
                ]);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @param mixed[] $input
     */
    public function sendThrowable(
        string $tenantName,
        string $method,
        string $path,
        array $input,
        ThrowableDocument $document
    ): void {
        if ($this->contextService->env() === 'test') {
            return;
        }

        $traceUrl = 'https://console.cloud.google.com/logs/analytics;' .
            'queryHandle=%7B%22Jbc%22:%7B%22viewResourceNames%22:%5B%22projects%2Fnektria%2Flocations' .
            '%2Fglobal%2Fbuckets%2F_Default%2Fviews%2F_Default%22%5D,%22queryType%22:%22LOGS_ANALYTICS_' .
            'QUERY_TYPE_SOURCE%22%7D,%22query%22:%22SELECT%5Cn%20%20timestamp,%20JSON_VALUE%2528labels.' .
            'app%2529,%20http_request.request_method%20as%20method,%20JSON_VALUE%2528json_payload.message' .
            '%2529%20as%20path,%20json_payload.request,%20json_payload.response,%20trace%5CnFROM%5Cn%20' .
            "%20%60nektria.global._Default._Default%60%5CnWHERE%5Cn%20%20trace%3D'__TRACE__'" .
            '%5CnORDER%20BY%20timestamp%20DESC%5Cn%22%7D;';

        $traceUrl = str_replace('__TRACE__', $this->contextService->traceId(), $traceUrl);
        $maxLength = 2000;
        $inputString = JsonUtil::encode($input, true);
        $documentString = JsonUtil::encode($document->toArray('dev'), true);
        $content = "‎\n" .
            "**{$this->contextService->project()}**\n" .
            "**{$tenantName}**\n" .
            "**{$method}** _{$path}_" .
            "```json\n" .
            $inputString .
            "\n```" .
            "```json\n" .
            $documentString .
            "\n```" .
            "Trace: [{$this->contextService->traceId()}]($traceUrl)\n" .
            "‎\n‎";

        if (strlen($content) >= $maxLength) {
            $content = "‎\n" .
                "**{$this->contextService->project()}**\n" .
                "**{$tenantName}**\n" .
                "**{$method}** _{$path}_ " .
                "```json\n" .
                $inputString .
                "\n```" .
                "```json\n" .
                $documentString .
                "\n```" .
                "Trace: {$this->contextService->traceId()}\n" .
                "‎\n‎";
        }

        $content = str_replace(['\/', '/app/'], ['/', ''], $content);
        $content = html_entity_decode(preg_replace('/\\\u([\da-fA-F]{4})/', '&#x\1;', $content) ?? '');

        try {
            $this->makeRequest('bugs', [
                'content' => $content
            ]);
        } catch (Throwable) {
            $content = "‎\n" .
                "**{$this->contextService->project()}**\n" .
                "**{$tenantName}**\n" .
                "**{$method}** _{$path}_ \n" .
                "Trace: {$this->contextService->traceId()}\n" .
                "‎\n‎";

            $this->makeRequest('bugs', [
                'content' => $content
            ]);
        }
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function channels(): array
    {
        return [
            'dev' => [
                'operations' => '1221387322755383356',
                'updates' => '1221387427000750120',
                'bugs' => '1221173694878060635',
                'pickingshifts' => '1221387354246221874',
                'configurations' => '1223608760287760545',
            ],
            'qa' => [
                'operations' => '1221387486320787518',
                'updates' => '1221387553375260714',
                'bugs' => '1221173866131623966',
                'pickingshifts' => '1221387520726663208',
                'configurations' => '1223608835256750293',
            ],
            'staging' => [
                'operations' => '1221387600485417000',
                'updates' => '1221387669410680893',
                'bugs' => '1221173888751239198',
                'pickingshifts' => '1221387636548309183',
                'configurations' => '1223609089490292796',
            ],
            'prod' => [
                'operations' => '1221221066450669678',
                'updates' => '1221224077482528908',
                'bugs' => '1221173937354833940',
                'pickingshifts' => '1221221171161468959',
                'configurations' => '1223571661421412352',
            ]
        ];
    }

    private function parseChannel(string $channelId): string
    {
        $defaultChannels = [
            'bugs',
            'operations',
            'updates',
            'pickingshifts',
            'configurations'
        ];

        if (!in_array($channelId, $defaultChannels, true)) {
            return $channelId;
        }

        $configuration = $this->channels();

        return $configuration[$this->contextService->env()][$channelId];
    }
}
