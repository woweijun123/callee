<?php

namespace Callee\Annotation;

use Attribute;
use Callee\CalleeCollector;
use Callee\CalleeData;
use Callee\CalleeEvent;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 注解调用
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Callee extends AbstractAnnotation
{
    public function __construct(public CalleeEvent|array $event, public ?string $scope = null, public int $priority = CalleeData::DEFAULT_PRIORITY, public bool $transaction = false)
    {
    }

    public function collectMethod(string $className, ?string $target): void
    {
        CalleeCollector::addCallee([$className, $target], $this->event, $this->scope, $this->priority, $this->transaction);
    }
}
