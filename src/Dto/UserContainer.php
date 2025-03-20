<?php

namespace Nektria\Dto;

use Nektria\Document\User;

class UserContainer
{
    private ?User $user;

    public function __construct()
    {
        $this->user = null;
    }

    /**
     * @param User|null $user
     */
    public function setUser(?User $user): void
    {
        $this->user = $user;
        LocalClock::defaultTimezone($user?->tenant->timezone);
    }

    /**
     * @return User|null
     */
    public function user(): ?User
    {
        return $this->user;
    }
}