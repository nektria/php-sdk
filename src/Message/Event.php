<?php

declare(strict_types=1);

namespace Nektria\Message;

abstract class Event
{
    abstract public function ref(): string;
}
