<?php

declare(strict_types=1);

namespace Nektria\Service;

use Nektria\Document\ThrowableDocument;
use Nektria\Dto\Clock;
use Nektria\Exception\NektriaRuntimeException;
use Nektria\Util\JsonUtil;
use Throwable;

use function count;
use function in_array;
use function strlen;

/**
 * @phpstan-type AlertMessage array{
 *      content?: string,
 *      embeds?: array<array{
 *          title?: string,
 *          type?: string,
 *          description?: string,
 *          url?: string,
 *          timestamp?: string,
 *          color?: int,
 *          footer?: array{
 *              text?: string,
 *              icon_url?: string
 *          },
 *          image?: array{
 *              url?: string,
 *              height?: int,
 *              width?: int
 *          },
 *          thumbnail?: array{
 *              url?: string,
 *              height?: int,
 *              width?: int
 *          },
 *          author?: array{
 *              name?: string,
 *          },
 *          fields?: array<array{
 *              name?: string,
 *              value?: string,
 *              inline?: bool
 *          }>,
 *      }>,
 *      components?: array<array{
 *          type: int,
 *          components: array<array{
 *              type: int,
 *              style: int,
 *              label: string,
 *              custom_id: string
 *          }>
 *      }>
 *  }
 */
class AlertService
{
    public const CHANNEL_BUGS = 'bugs';

    public const CHANNEL_OPERATIONS = 'operations';

    public const CHANNEL_UPDATES = 'updates';

    public const CHANNEL_PICKING_SHIFTS = 'pickingshifts';

    public const CHANNEL_CONFIGURATIONS = 'configurations';

    public const EMPTY_LINE = "‎\n‎";

    public const FLAG_SUPPRESS_NOTIFICATIONS = 1 << 12;

    /** @var string[] */
    private array $tokens;

    public function __construct(
        private readonly string $alertsToken,
        private readonly ContextService $contextService,
        private readonly RequestClient $requestClient,
        private readonly SharedDiscordCache $sharedDiscordCache,
        private readonly UserService $userService,
    ) {
        $this->tokens = explode(',', $this->alertsToken);
    }

    /**
     * @return string[]
     */
    public function channelsList(): array
    {
        return array_keys($this->channels()[$this->contextService->env()]);
    }

    /**
     * @param AlertMessage $message
     */
    private function makeRequest(string $channel, array $message): void
    {
        if ($this->contextService->env() === 'test') {
            return;
        }

        $token = $this->tokens[array_rand($this->tokens)];
        $channelId = $this->parseChannel($channel);
        $this->sharedDiscordCache->addMessage($channel, $message);
        $this->requestClient->post(
            "https://discord.com/api/channels/{$channelId}/messages",
            $message,
            [
                'Authorization' => "Bot {$token}"
            ]
        );
        $this->sharedDiscordCache->removeLastMessage($channel);
    }

    /**
     * @param array{
     *     name: string,
     *     value: string,
     * }[] $embeds
     */
    public function simpleMessage(
        string $channel,
        string $message,
        array $embeds = [],
        ?int $flags = null
    ): void {
        $end = '';
        if (count($embeds) === 0) {
            $end = self::EMPTY_LINE;
        } else {
            $embeds = [
                [
                    'fields' => $embeds
                ]
            ];
        }

        $this->sendMessage($channel, [
            'content' => $message . $end,
            'embeds' => $embeds
        ], $flags);
    }

    /**
     * @param AlertMessage $message
     */
    public function sendMessage(string $channel, array $message, ?int $flags = null): void
    {
        $hour = Clock::new()->setTimezone('Europe/Madrid')->hour();
        if ($hour < 8 || $hour > 23) {
            $flags |= self::FLAG_SUPPRESS_NOTIFICATIONS;
        }

        $eol = self::EMPTY_LINE;
        $tenantName = $this->userService->user()?->tenant->name ?? 'none';
        $message['content'] ??= '';
        $message['content'] =
            $eol .
            "**{$this->contextService->project()}**{$eol}" .
            "**{$tenantName}**{$eol}" .
            $eol .
            $message['content'];

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
                    'content' => $content,
                    'flags' => $flags
                ]);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @param AlertMessage $message
     */
    public function debugMessage(array $message, ?int $flags = null): void
    {
        if (!$this->contextService->debugMode()) {
            return;
        }

        $hour = Clock::new()->setTimezone('Europe/Madrid')->hour();
        if ($hour < 8 || $hour > 23) {
            $flags |= self::FLAG_SUPPRESS_NOTIFICATIONS;
        }

        $eol = self::EMPTY_LINE;
        $tenantName = $this->userService->user()?->tenant->name ?? 'none';
        $message['content'] ??= '';
        $message['content'] =
            $eol .
            "**{$this->contextService->project()}**{$eol}" .
            "**{$tenantName}**{$eol}" .
            $eol .
            $message['content'];

        try {
            $this->makeRequest('debug', $message);
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

                $this->makeRequest('debug', [
                    'content' => $content,
                    'flags' => $flags
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
        ThrowableDocument $document,
        int $times = 1,
        ?int $flags = null
    ): void {
        if ($this->contextService->env() === 'test') {
            return;
        }

        if (
            ($document->throwable instanceof NektriaRuntimeException)
            && $document->throwable->silent() && !$this->contextService->debugMode()
        ) {
            return;
        }

        $hour = Clock::new()->setTimezone('Europe/Madrid')->hour();
        if ($hour < 8 || $hour > 23) {
            $flags |= self::FLAG_SUPPRESS_NOTIFICATIONS;
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
        $documentString = JsonUtil::encode($document->toDevArray(), true);
        $eol = self::EMPTY_LINE;
        $manyTimes = $times === 1 ? '' : " (x{$times})";
        $content =
            $eol .
            "**{$this->contextService->project()}**{$eol}" .
            "**{$tenantName}**{$eol}" .
            $eol .
            "**{$method}** _{$path}_ {$manyTimes}\n" .
            "```json\n" .
            $inputString .
            "\n```" .
            "```json\n" .
            $documentString .
            "\n```" .
            "Trace: [{$this->contextService->traceId()}]($traceUrl)\n" .
            self::EMPTY_LINE;

        if (strlen($content) >= $maxLength) {
            $content = self::EMPTY_LINE .
                $eol .
                "**{$this->contextService->project()}**{$eol}" .
                "**{$tenantName}**{$eol}" .
                $eol .
                "**{$method}** _{$path}_ {$manyTimes}\n" .
                "```json\n" .
                $inputString .
                "\n```" .
                "```json\n" .
                $documentString .
                "\n```" .
                "Trace: {$this->contextService->traceId()}\n" .
                self::EMPTY_LINE;
        }

        $content = str_replace(['\/', '/app/'], ['/', ''], $content);
        $content = html_entity_decode(preg_replace('/\\\u([\da-fA-F]{4})/', '&#x\1;', $content) ?? '');

        try {
            $this->makeRequest(self::CHANNEL_BUGS, [
                'content' => $content
            ]);
        } catch (Throwable) {
            $content = self::EMPTY_LINE .
                $eol .
                "**{$this->contextService->project()}**{$eol}" .
                "**{$tenantName}**{$eol}" .
                $eol .
                "**{$method}** _{$path}_ {$manyTimes}\n" .
                "Trace: {$this->contextService->traceId()}\n" .
                self::EMPTY_LINE;

            $this->makeRequest(self::CHANNEL_BUGS, [
                'content' => $content,
                'flags' => $flags
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
                'debug' => '1235335765550956654'
            ],
            'qa' => [
                'operations' => '1221387486320787518',
                'updates' => '1221387553375260714',
                'bugs' => '1221173866131623966',
                'pickingshifts' => '1221387520726663208',
                'configurations' => '1223608835256750293',
                'debug' => '1235335567089209404'
            ],
            'staging' => [
                'operations' => '1221387600485417000',
                'updates' => '1221387669410680893',
                'bugs' => '1221173888751239198',
                'pickingshifts' => '1221387636548309183',
                'configurations' => '1223609089490292796',
                'debug' => '1235335500974264350'
            ],
            'prod' => [
                'operations' => '1221221066450669678',
                'updates' => '1221224077482528908',
                'bugs' => '1221173937354833940',
                'pickingshifts' => '1221221171161468959',
                'configurations' => '1223571661421412352',
                'debug' => '1235335372506923068',
            ]
        ];
    }

    /**
     * @return AlertMessage[]
     */
    public function readMessagesFromCache(string $channel): array
    {
        return $this->sharedDiscordCache->read($channel);
    }

    public function cleanMessagesFromCache(string $channel): void
    {
        $this->sharedDiscordCache->remove($channel);
    }

    private function parseChannel(string $channelId): string
    {
        $defaultChannels = [
            'bugs',
            'operations',
            'updates',
            'pickingshifts',
            'configurations',
            'debug'
        ];

        if (!in_array($channelId, $defaultChannels, true)) {
            return $channelId;
        }

        $configuration = $this->channels();

        return $configuration[$this->contextService->env()][$channelId];
    }
}
