<?php

namespace Callee;

use Hyperf\Database\ConnectionResolverInterface;

/**
 * 数据库事务管理器解析器。
 */
trait TransactionManagerResolver
{
    /**
     * @Inject
     * @var ConnectionResolverInterface
     */
    protected $db;

    /**
     * 数据库事务管理器解析器实例
     * @var callable
     */
    public $transactionManagerResolver;

    /**
     * 解析数据库事务管理器
     * @return ConnectionResolverInterface|null
     */
    public function resolveTransactionManager(): ?ConnectionResolverInterface
    {
        if ($this->transactionManagerResolver) {
            return call_user_func($this->transactionManagerResolver);
        }

        return $this->db ?? null;
    }

    /**
     * 设置数据库事务管理器解析器
     * @param callable $resolver
     * @return $this
     */
    public function setTransactionManagerResolver(callable $resolver): static
    {
        $this->transactionManagerResolver = $resolver;

        return $this;
    }
}
