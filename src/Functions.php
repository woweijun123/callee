<?php

declare(strict_types=1);

namespace Hyperf\Config;

use Callee\CalleeEvent;
use Callee\Exception\CalleeException;
use Callee\Reflection;
use Hyperf\Context\ApplicationContext;
use Psr\Container\NotFoundExceptionInterface;

function call(CalleeEvent|array|null $event, array $args = [], bool $strict = false, mixed $default = null, ?string $scope = null): mixed
{
    try {
        return ApplicationContext::getContainer()->get(Reflection::class)->call($event, $args, $strict, $default, $scope);
    } catch (NotFoundExceptionInterface $e) {
        throw new CalleeException($e->getMessage());
    }
}
