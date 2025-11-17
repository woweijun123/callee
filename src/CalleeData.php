<?php

declare(strict_types=1);

namespace Callee;

/**
 * CalleeData 类
 *
 * 用于封装和存储通过注解（Attribute）收集到的，需要被调用的方法（Callee）的详细信息。
 * 它是事件调用链数据结构的基础。
 */
class CalleeData
{
    /**
     * 默认优先级
     * 值为 0，用于确定方法的默认执行顺序。
     */
    public const int DEFAULT_PRIORITY = 0;

    /**
     * 构造函数
     *
     * @param array $callee 需要被调用的方法信息数组，通常是 [类名, 方法名]。
     * @param array $mapper 映射器/数据映射，可能包含调用所需的额外参数或上下文信息。
     * @param int $priority 优先级，值越高，执行顺序越靠前（默认 0）。
     * @param bool $afterCommit 是否在数据库事务成功提交后才执行此方法，默认否。
     */
    public function __construct(
        public array $callee,
        public array $mapper,
        public int   $priority,
        public bool  $afterCommit = false
    )
    {
    }
}
