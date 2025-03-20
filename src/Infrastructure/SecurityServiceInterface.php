<?php

declare(strict_types=1);

namespace Nektria\Infrastructure;

use Nektria\Document\User;

interface SecurityServiceInterface
{
    public function authenticateApi(string $apiKey): void;

    public function authenticateSystem(string $tenantId): void;

    public function authenticateUser(string $apiKey): void;

    public function clearAuthentication(): void;

    public function retrieve(string $id): User;

    public function retrieveUser(): User;

    public function user(): ?User;

    /**
     * @param string[] $roles
     */
    public function validateRole(array $roles): void;
}
