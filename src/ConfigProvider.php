<?php

declare(strict_types=1);

namespace Callee;

use XHyperf\Invoker\Listener\ImplRegister;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
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
