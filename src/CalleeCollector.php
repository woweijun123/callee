<?php

namespace Callee;

use Callee\Annotation\Mapper;
use Hyperf\Di\MetadataCollector;
use Hyperf\Stdlib\SplPriorityQueue;
use ReflectionException;

class CalleeCollector extends MetadataCollector
{
    /**
     * 容器
     * @var CalleeData[]
     */
    protected static array $container = [];

    /**
     * 添加被调用方法到容器
     * @param array             $callable [类名, 方法名]
     * @param CalleeEvent|array $event    事件枚举 或 [命名空间, 事件名] 格式的数组
     * @param string|null       $scope    作用域
     * @param int               $priority 优先级
     * @return void
     */
    public static function addCallee(array $callable, CalleeEvent|array $event, ?string $scope = null, int $priority = CalleeData::DEFAULT_PRIORITY, bool $transaction = false): void
    {
        try {
            $parameters = Reflection::reflectMethod(...$callable)->getParameters();
        } catch (ReflectionException) {
            $parameters = [];
        }
        // 获取参数映射
        $mapper = [];
        foreach ($parameters as $parameter) {
            $name                 = $parameter->getName();
            $reflectionAttributes = $parameter->getAttributes(Mapper::class);
            foreach ($reflectionAttributes as $attribute) {
                $instance = $attribute->newInstance();
                foreach ($instance->src as $src) {
                    $mapper[$src] = $name;
                }
            }
            // 默认参数名优先级最低
            $mapper[$name] = $name;
        }
        // 获取事件命名空间和事件名
        [$namespace, $eventName] = self::getEvent($event, $scope);
        // 添加到容器
        static::$container[$namespace][$eventName][] = new CalleeData($callable, $mapper, $priority, $transaction);
    }

    /**
     * 检查是否存在对应的调用方法
     * @param CalleeEvent|array|null $event 事件枚举 或 [命名空间, 事件名] 格式的数组
     * @param string|null            $scope 作用域
     * @return bool
     */
    public static function hasCallee(CalleeEvent|array|null $event, ?string $scope = null): bool
    {
        if (!$event) {
            return false;
        }
        [$namespace, $eventName] = self::getEvent($event, $scope);
        return isset(static::$container[$namespace][$eventName]);
    }

    /**
     * 获取调用方法及参数映射
     * @param CalleeEvent|array|null $event 事件枚举 或 [命名空间, 事件名] 格式的数组
     * @param string|null $scope 作用域
     * @return SplPriorityQueue|null
     */
    public static function getCallee(CalleeEvent|array|null $event, ?string $scope = null): ?SplPriorityQueue
    {
        if (!$event) {
            return null;
        }
        [$namespace, $eventName] = self::getEvent($event, $scope);
        /**
         * @var CalleeData[] $calleeList
         */
        $calleeList = static::$container[$namespace][$eventName];
        if (!$calleeList) {
            return null;
        }
        // 创建优先级队列并按优先级排序
        $queue = new SplPriorityQueue();
        foreach ($calleeList as $callee) {
            $queue->insert($callee, $callee->priority);
        }
        return $queue;
    }

    /**
     * 解析事件命名空间和事件名
     * @param CalleeEvent|array $event 事件枚举 或 [命名空间, 事件名] 格式的数组
     * @param string|null       $scope 作用域
     * @return array
     */
    public static function getEvent(CalleeEvent|array $event, ?string $scope = null): array
    {
        [$namespace, $eventName] = is_array($event) ? $event : [$event->namespace(), $event->event()];
        return [$scope ?: $namespace, $eventName];
    }

    /**
     * 清除容器数据
     * @param string|null $key 类名（可选）
     * @return void
     */
    public static function clear(?string $key = null): void
    {
        if ($key) {
            foreach (static::$container as $namespace => $events) {
                foreach ($events as $event => $callableList) {
                    /* @var CalleeData $callable */
                    foreach ($callableList as $callable) {
                        if ($callable->callee[0] === $key) {
                            unset(static::$container[$namespace][$event]);
                        }
                    }
                }
            }
        } else {
            static::$container = [];
        }
    }
}
