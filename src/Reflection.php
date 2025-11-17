<?php

namespace Callee;

use Hyperf\Collection\Arr;
use Hyperf\Context\ApplicationContext;
use Hyperf\Coroutine\Coroutine;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use function Hyperf\Support\value;

class Reflection
{
    /**
     * 缓存容器数据
     * @var array
     */
    protected static array $container = [];

    /**
     * 注解反射调用，根据 Callee 注解调用相关函数
     * @param CalleeEvent|array|null $event   事件枚举 或 [命名空间, 事件名] 格式的数组
     * @param array                  $args    参数
     * @param bool                   $strict  是否严格模式，为true时，空参数都会使用默认值
     * @param mixed|null             $default 默认值，注解方法不存在时返回
     * @param string|null            $scope   作用域
     * @return mixed
     */
    public static function call(CalleeEvent|array|null $event, array $args = [], bool $strict = false, mixed $default = null, ?string $scope = null): mixed
    {
        $callableDataList = CalleeCollector::getCallee($event, $scope);
        if (!$callableDataList) {
            return value($default, $args);
        }
        $result = [];
         /* @var CalleeData $calleeData */
        foreach ($callableDataList as $calleeData) {
            // 若实现了 ShouldDispatchAfterCommit 接口，则检查是否在事务中
            if ($calleeData->callee->afterCommit && Db::connection()->isTransaction()) {
                DatabaseTransactionRecord::instance()->addCallback(function () use ($calleeData, $args, $strict) {
                    self::invoke($calleeData, $args, $strict);
                });
            } else {
                $result[] = self::invoke($calleeData, $args, $strict);
            }
        }
        return count($result) == 1 ? array_shift($result) : $result;
    }

    /**
     * callable 方式反射调用
     * @param CalleeData $calleeData CalleeData 类
     * @param array $args     参数
     * @param bool  $strict   是否严格模式，为true时，空参数都会使用默认值
     * @return mixed
     * @throws
     */
    public static function invoke(CalleeData $calleeData, array $args = [], bool $strict = false): mixed
    {
        [$class, $method] = $calleeData->callable;
        if (is_string($class)) {
            $class = ApplicationContext::getContainer()->get($class);
        }
        $className = get_class($class);
        // 返回获取的默认参数值
        $reflect      = static::reflectParameters($className, $method);
        $parameters   = [];
        $missingValue = Str::random(10);
        // 转换参数映射
        $argsMapper = [];
        foreach ($calleeData->mapper as $src => $target) {
            $val = Arr::get($args, $src, $missingValue);
            if ($val !== $missingValue && !isset($argsMapper[$target])) {
                $argsMapper[$target] = $val;
            }
        }
        // 没有映射时使用原参数
        $argsMapper = $argsMapper ?: $args;
        foreach ($reflect as $name => $default) {
            // 参数值获取顺序：注解key -> 参数key -> 默认值或null
            $val          = Arr::get($argsMapper, $name, $default);
            $parameters[] = $strict ? ($val ?: $default) : $val;
        }
        if ($calleeData->callee->async) {
            return Coroutine::create(fn() => $class->$method(...$parameters));
        } else {
            return $class->$method(...$parameters);
        }
    }

    /**
     * 反射类/接口/trait。
     * 获取并缓存 ReflectionClass 实例。
     * @param string $className 类、接口或 trait 的完全限定名。
     * @return ReflectionClass ReflectionClass 实例。
     * @throws InvalidArgumentException 如果类/接口/trait 不存在。
     */
    public static function reflectClass(string $className): ReflectionClass
    {
        if (!isset(static::$container['class'][$className])) {
            if (!class_exists($className) && !interface_exists($className) && !trait_exists($className)) {
                throw new InvalidArgumentException("Class {$className} not exist");
            }
            static::$container['class'][$className] = new ReflectionClass($className);
        }

        return static::$container['class'][$className];
    }

    /**
     * 反射方法。
     * 获取并缓存 ReflectionMethod 实例。
     * @param string $className 方法所属的类或 trait 名。
     * @param string $method    要反射的方法名。
     * @return ReflectionMethod ReflectionMethod 实例。
     * @throws InvalidArgumentException 如果类/trait 不存在。
     * @throws ReflectionException 如果方法不存在。
     */
    public static function reflectMethod(string $className, string $method): ReflectionMethod
    {
        $key = $className . '::' . $method;
        if (!isset(static::$container['method'][$key])) {
            if (!class_exists($className) && !trait_exists($className)) {
                throw new InvalidArgumentException("Class $className not exist");
            }
            if (!method_exists($className, $method)) {
                throw new InvalidArgumentException("Method $method does not exist in class $className.");
            }
            static::$container['method'][$key] = static::reflectClass($className)->getMethod($method);
        }

        return static::$container['method'][$key];
    }

    /**
     * 反射属性。
     * 获取并缓存 ReflectionProperty 实例。
     * @param string $className 属性所属的类名。
     * @param string $property  要反射的属性名。
     * @return ReflectionProperty ReflectionProperty 实例。
     * @throws InvalidArgumentException 如果类不存在。
     * @throws ReflectionException 如果属性不存在。
     */
    public static function reflectProperty(string $className, string $property): ReflectionProperty
    {
        $key = $className . '::' . $property;
        if (!isset(static::$container['property'][$key])) {
            static::$container['property'][$key] = static::reflectClass($className)->getProperty($property);
        }

        return static::$container['property'][$key];
    }

    /**
     * 反射方法参数。
     * 获取并缓存方法参数名及其默认值。
     * @param string $className 方法所属的类名。
     * @param string $method    要反射参数的方法名。
     * @return array 参数名 => 默认值的关联数组。
     * @throws ReflectionException 如果方法不存在。
     */
    public static function reflectParameters(string $className, string $method): array
    {
        $key = $className . '::' . $method;
        if (!isset(static::$container['parameter'][$key])) {
            $reflectionParameters                 = static::reflectMethod($className, $method)->getParameters();
            $arrayReduce                          = array_reduce($reflectionParameters, function ($carry, $param) {
                $carry[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;

                return $carry;
            });
            static::$container['parameter'][$key] = $arrayReduce ?? [];
        }

        return static::$container['parameter'][$key];
    }
}
