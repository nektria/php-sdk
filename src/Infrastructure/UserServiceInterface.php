<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\User;

interface UserServiceInterface
{
    /**
     * @param string[] $roles
     */
    public function validateRole(array $roles): void;

    public function authenticateSystem(string $tenantId): void;

    public function authenticateUser(string $apiKey): void;

    public function authenticateApi(string $apiKey): void;

    public function clearAuthentication(): void;

    public function user(): ?User;

    public function retrieveUser(): User;
}
