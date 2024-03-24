<?php

declare(strict_types=1);

namespace Nektria\Interface;

interface UserServiceInterface
{
    /**
     * @param string[] $roles
     */
    public function validateRoles(array $roles): void;

    public function impersonateSystemUser(string $tenantId): void;

    public function unimpersonateSystemUser(): void;
}
