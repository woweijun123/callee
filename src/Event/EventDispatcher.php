<?php

declare(strict_types=1);

namespace Callee\Event;

use Callee\DatabaseTransactionRecord;
use Callee\ShouldDispatchAfterCommit;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;

class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private ListenerProviderInterface $listeners,
        private ?LoggerInterface          $logger = null
    )
    {
    }

    /**
     * Provide all listeners with an event to process.
     * @param object $event The object to process
     * @return object The Event that was passed, now modified by listeners
     */
    public function dispatch(object $event): object
    {
        // 若实现了 ShouldDispatchAfterCommit 接口，则检查是否在事务中
        if ($event instanceof ShouldDispatchAfterCommit) {
            DatabaseTransactionRecord::instance()->addCallback(function () use ($event) {
                $this->invoke($event);
            });

            return $event;
        }
        $this->invoke($event);

        return $event;
    }

    /**
     * Dump the debug message if $logger property is provided.
     * @param mixed $listener
     * @param object $event
     */
    private function dump(mixed $listener, object $event): void
    {
        if (!$this->logger) {
            return;
        }
        $eventName = get_class($event);
        $listenerName = '[ERROR TYPE]';
        if (is_array($listener)) {
            $listenerName = is_string($listener[0]) ? $listener[0] : get_class($listener[0]);
        } elseif (is_string($listener)) {
            $listenerName = $listener;
        } elseif (is_object($listener)) {
            $listenerName = get_class($listener);
        }
        $this->logger->debug(sprintf('Event %s handled by %s listener.', $eventName, $listenerName));
    }

    /**
     * @param object $event
     * @return void
     */
    protected function invoke(object $event): void
    {
        foreach ($this->listeners->getListenersForEvent($event) as $listener) {
            $listener($event);
            $this->dump($listener, $event);
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }
    }
}
