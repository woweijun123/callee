<?php

namespace Callee\Annotation;

use Attribute;
use Callee\CalleeCollector;
use Callee\CalleeData;
use Callee\CalleeEvent;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 注解调用 (Callee Annotation)
 *
 * 这是一个自定义的注解（在 PHP 8 中称为 Attribute），
 * 用于标记需要被特定事件调用的方法。
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Callee extends AbstractAnnotation
{
    /**
     * @param CalleeEvent|array $event 事件或事件列表，用于触发该方法的调用。
     * @param string|null $scope 作用域，可选参数，用于限制该注解的作用范围。
     * @param int $priority 优先级，用于决定多个 Callee 在同一事件中被调用的顺序。
     * @param bool $afterCommit 是否在事务成功 提交 (Commit) 后才执行该方法。
     */
    public function __construct(
        public CalleeEvent|array $event,
        public ?string $scope = null,
        public int $priority = CalleeData::DEFAULT_PRIORITY,
        public bool $afterCommit = false
    ) {
    }

    /**
     * 收集方法注解
     *
     * 该方法是 Hyperf/Di 框架注解收集机制的一部分，在框架扫描到此注解时调用。
     * 它将带注解的方法信息添加到 CalleeCollector 中。
     *
     * @param string $className 目标方法所属的类名。
     * @param string|null $target 目标方法的方法名。
     * @return void
     */
    public function collectMethod(string $className, ?string $target): void
    {
        // 将方法信息、事件、作用域、优先级和事务状态添加到收集器中。
        CalleeCollector::addCallee(
            [$className, $target], // 目标方法：[类名, 方法名]
            $this->event,          // 触发事件
            $this->scope,          // 作用域
            $this->priority,       // 优先级
            $this->afterCommit     // 是否开启事务
        );
    }
}
