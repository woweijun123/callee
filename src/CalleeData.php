<?php

declare(strict_types=1);

namespace Callee;

class CalleeData
{
    public const int DEFAULT_PRIORITY = 0;

    public function __construct(public array $callee, public array $mapper, public int $priority)
    {
    }
}
