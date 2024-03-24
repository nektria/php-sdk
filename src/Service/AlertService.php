<?php

declare(strict_types=1);

namespace Nektria\Service;

use RuntimeException;
use Throwable;

use function in_array;
use function is_string;

class AlertService
{
    private string $token;

    public function __construct(
        private readonly ContextService $contextService,
        private readonly RequestClient $requestClient,
    ) {
        $token = getenv('ALERTS_TOKEN');
        if (!is_string(getenv('ALERTS_TOKEN'))) {
            throw new RuntimeException('ALERTS_TOKEN envvar is not set.');
        }

        $this->token = (string) $token;
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
        if ($this->contextService->env() === 'test') {
            return;
        }

        try {
            $channel = $this->parseChannel($channel);
            $this->requestClient->post(
                "https://discord.com/api/channels/{$channel}/messages",
                $message,
                [
                    'Authorization' => "Bot {$this->token}"
                ]
            );
        } catch (Throwable) {
        }
    }

    private function parseChannel(string $channelId): string
    {
        $defaultChannels = ['bugs', 'operations', 'updates', 'pickingshifts'];

        if (!in_array($channelId, $defaultChannels, true)) {
            return $channelId;
        }

        $configuration = [
            'dev' => [
                'operations' => '1221387322755383356',
                'updates' => '1221387427000750120',
                'bugs' => '1221173694878060635',
                'pickingshifts' => '1221387354246221874'
            ],
            'qa' => [
                'operations' => '1221387486320787518',
                'updates' => '1221387553375260714',
                'bugs' => '1221173866131623966',
                'pickingshifts' => '1221387520726663208'
            ],
            'staging' => [
                'operations' => '1221387600485417000',
                'updates' => '1221387669410680893',
                'bugs' => '1221173888751239198',
                'pickingshifts' => '1221387636548309183'
            ],
            'prod' => [
                'operations' => '1221221066450669678',
                'updates' => '1221224077482528908',
                'bugs' => '1221173937354833940',
                'pickingshifts' => '1221221171161468959'
            ]
        ];

        return $configuration[$this->contextService->env()][$channelId];
    }
}
