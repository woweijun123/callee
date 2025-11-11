<?php

declare(strict_types=1);

namespace Callee;

use Callee\Listener\ImplRegister;
use Hyperf\Event\EventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                // 注册事件分发器
                EventDispatcherInterface::class => EventDispatcher::class
            ],
            'listeners'    => [
                ImplRegister::class,
            ],
            'annotations'  => [
                'scan' => [
                    'collectors' => [
                        CalleeCollector::class,
                    ],
                ],
            ],
            'aspects'      => [
            ],
            'publish'      => [
            ],
        ];
    }
}
