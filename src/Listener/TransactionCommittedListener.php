<?php

declare(strict_types=1);

namespace App\Listener\Transaction;

use Callee\DatabaseTransactionRecord;
use Hyperf\Database\Events\TransactionCommitted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;

#[Listener]
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
