<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

interface UserServiceInterface
{
    /**
     * @param string[] $roles
     */
    public function validateRole(array $roles): void;

    public function authenticateSystem(string $tenantId): void;

    public function authenticateUser(string $apiKey): void;

    public function authenticateAdmin(string $apiKey, string $tenantId): void;

    public function clearAuthentication(): void;

    public function retrieveTenantName(): string;
}
