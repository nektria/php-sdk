<?php

declare(strict_types=1);

namespace App\MessageHandler\__ENTITY__;

use App\Message\__ENTITY__\__MESSAGE__;
use Nektria\Exception\NektriaException;
use Nektria\Message\MessageHandler;

readonly class __MESSAGE__Handler extends MessageHandler
{
    public function __invoke(__MESSAGE__ $message): void
    {
        throw new NektriaException('Not implemented');
    }
}
