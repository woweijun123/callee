<?php

declare(strict_types=1);

namespace Callee\Listener;

use Callee\DatabaseTransactionRecord;
use Hyperf\Database\Events\TransactionRolledBack;
use Hyperf\Event\Contract\ListenerInterface;

class TransactionRollbackListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            TransactionRolledBack::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$event instanceof TransactionRolledBack) {
            return;
        }
        DatabaseTransactionRecord::instance()->flush();
    }
}
