<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

interface UserServiceInterface
{
    /**
     * @param string[] $roles
     */
    public function validateRoles(array $roles): void;

    public function impersonateSystemUser(string $tenantId): void;

    public function authenticateByApiKey(string $apiKey): void;

    public function authenticateAdminByApiKey(string $apiKey, string $tenantId): void;

    public function unimpersonateSystemUser(): void;

    public function retrieveTenantName(string $id): string;
}
