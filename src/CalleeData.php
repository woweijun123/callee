<?php

declare(strict_types=1);

namespace Callee;

class CalleeData
{
    public const int DEFAULT_PRIORITY = 0;

    /**
     * @var callable
     */
    public $callee;

    public function __construct(callable $callee, public array $mapper, public int $priority)
    {
        $this->callee = $callee;
    }
}
