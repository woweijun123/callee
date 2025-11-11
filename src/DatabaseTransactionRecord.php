<?php

declare(strict_types=1);

namespace Callee;

use Hyperf\Support\Traits\StaticInstance;

/**
 * 数据库事务记录类
 * 用于记录事务的连接信息、层级以及在提交或回滚时需要执行的回调函数
 */
class DatabaseTransactionRecord
{
    use StaticInstance;

    /**
     * 在事务提交后应该执行的回调函数集合
     * @var array<callable>
     */
    protected array $callbacks = [];

    /**
     * 在事务回滚后应该执行的回调函数集合
     * @var array<callable>
     */
    protected array $callbacksForRollback = [];

    /**
     * 注册一个在事务提交后执行的回调函数
     * @param callable $callback 待注册的回调函数
     */
    public function addCallback(callable $callback): void
    {
        $this->callbacks[] = $callback;
    }

    /**
     * 执行所有注册的提交后回调函数
     */
    public function executeCallbacks(): void
    {
        foreach ($this->callbacks as $callback) {
            $callback();
        }
    }

    /**
     * 获取所有注册的提交后回调函数
     * @return array<callable>
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }

    /**
     * 注册一个在事务回滚后执行的回调函数
     * @param callable $callback 待注册的回调函数
     */
    public function addCallbackForRollback(callable $callback): void
    {
        $this->callbacksForRollback[] = $callback;
    }

    /**
     * 执行所有注册的回滚后回调函数
     */
    public function executeCallbacksForRollback(): void
    {
        foreach ($this->callbacksForRollback as $callback) {
            $callback();
        }
    }

    /**
     * 获取所有注册的回滚后回调函数
     * @return array<callable>
     */
    public function getCallbacksForRollback(): array
    {
        return $this->callbacksForRollback;
    }

    /**
     * 清空所有注册的回调函数
     * @return void
     */
    public function flush(): void
    {
        // 清空所有注册的回调函数
        $this->callbacks            = [];
        $this->callbacksForRollback = [];
    }
}
