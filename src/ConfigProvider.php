<?php

declare(strict_types=1);

namespace Callee;

use Callee\Listener\ImplRegister;
use Callee\Listener\TransactionCommittedListener;
use Callee\Listener\TransactionRollbackListener;
use Callee\Event\EventDispatcher;
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
                TransactionCommittedListener::class,
                TransactionRollbackListener::class,
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
