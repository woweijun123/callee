<?php

declare(strict_types=1);

namespace Callee\Listener;

use Callee\DatabaseTransactionRecord;
use Hyperf\Database\Events\TransactionCommitted;
use Hyperf\Event\Contract\ListenerInterface;

class TransactionCommittedListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            TransactionCommitted::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$event instanceof TransactionCommitted) {
            return;
        }
        if ($event->connection->transactionLevel() == 0) {
            DatabaseTransactionRecord::instance()->executeCallbacks();
        }
    }
}
