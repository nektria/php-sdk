<?php

declare(strict_types=1);

namespace App\Message\__ENTITY__;

use Nektria\Message\Command;
use Nektria\Service\RoleManager;
use Nektria\Util\Annotation\RolesRequired;

#[RolesRequired([RoleManager::ROLE_USER])]
readonly class __MESSAGE__ implements Command
{
    public function __construct() {
    }

    public function ref(): string
    {
        return '???';
    }
}
