<?php

declare(strict_types=1);

namespace Nektria\Util\Annotation;

use Attribute;

#[Attribute]
class IgnoreProperty
{
    public function __construct()
    {
    }
}
