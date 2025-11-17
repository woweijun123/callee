<?php

declare(strict_types=1);

namespace Callee;

use Callee\Annotation\Callee;

/**
 * 封装和存储通过注解收集到的、待调用的方法（Callee）的详细信息
 */
class CalleeData
{
    /**
     * 优先级「值越大越优先」
     */
    public const int DEFAULT_PRIORITY = 0;

    /**
     * 构造函数
     *
     * @param array $callable 需要被调用的方法信息数组，通常是 [类名, 方法名]。
     * @param array $mapper 映射器/数据映射，可能包含调用所需的额外参数或上下文信息。
     * @param Callee $callee Callee 注解
     */
    public function __construct(public array $callable, public array $mapper, public Callee $callee)
    {
    }
}
