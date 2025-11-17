<?php

namespace Callee\Annotation;

use Attribute;
use Callee\CalleeCollector;
use Callee\CalleeData;
use Callee\CalleeEvent;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 调用注解 (Callee Annotation)
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Callee extends AbstractAnnotation
{
    /**
     * @param CalleeEvent|array $event 事件或事件列表，用于触发该方法的调用。
     * @param string|null $scope 作用域，可选参数，用于限制该注解的作用范围。
     * @param int $priority 优先级，用于决定多个 Callee 在同一事件中被调用的顺序。
     * @param bool $afterCommit 是否在事务成功 提交 (Commit) 后才执行该方法。
     * @param bool $async 是否异步执行该方法。
     */
    public function __construct(
        public CalleeEvent|array $event,
        public ?string           $scope = null,
        public int               $priority = CalleeData::DEFAULT_PRIORITY,
        public bool              $afterCommit = false,
        public bool              $async = false,
    )
    {
    }

    /**
     * 收集方法注解
     * 该方法是 Hyperf/Di 框架注解收集机制的一部分，在框架扫描到此注解时调用，它将带注解的方法信息添加到 CalleeCollector 中。
     * @param string $className 目标方法所属的类名。
     * @param string|null $target 目标方法的方法名。
     * @return void
     */
    public function collectMethod(string $className, ?string $target): void
    {
        // 将方法信息、事件、作用域、优先级和事务状态添加到收集器中。
        CalleeCollector::addCallee([$className, $target], $this);
    }
}
