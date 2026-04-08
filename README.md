# Callee 库使用文档

## 简介

Callee 是一个基于 Hyperf 框架的事件调用管理库，用于简化事件驱动的代码组织和调用。它提供了一种优雅的方式来注册和调用事件处理方法，支持注解方式和直接代码方式注册。

## 功能

- 通过方法注解、反射机制实现轻量级事件回调「将`#[Callee(枚举)]`绑定到任意方法进行回调」
- 功能支持：
    - 发布订阅
    - 优先级
    - 协程异步
    - 事务后执行

## 安装

要通过 Composer 安装此包，请运行以下命令：

```bash
composer require riven/hyperf-callee
```

## 使用

### 配置

`Callee\ConfigProvider::class`服务提供者应该会自动注册。如果未自动注册，在你的 Hyperf 应用程序中启用 `Callee`，你需要在 `config/autoload/dependencies.php` 文件中注册它：

```php
return [
    Callee\ConfigProvider::class,
];
```

### 1. 定义事件枚举

首先，你需要定义事件枚举，实现 `CalleeEvent` 接口：

```php
<?php

namespace App\Event;

use Callee\CalleeEvent;
use Callee\CalleeEventTrait;

enum Action: string implements CalleeEvent
{
    use CalleeEventTrait;

    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Read = 'read';
}
```

### 2. 注册事件处理方法

#### 方式 1：使用注解注册

```php
<?php

namespace App\Service;

use App\Event\Action;
use Callee\Annotation\Callee;

class UserService
{
    #[Callee(Action::Create)]
    public function createUser(string $name, string $email): array
    {
        // 处理创建用户的逻辑
        return ['id' => 1, 'name' => $name, 'email' => $email];
    }

    #[Callee(Action::Update, async: true)]
    public function updateUser(int $id, array $data): bool
    {
        // 处理更新用户的逻辑
        return true;
    }
}
```

#### 方式 2：直接代码注册

```php
<?php

use App\Event\Action;
use App\Service\UserService;
use Callee\Annotation\Callee;
use Callee\CalleeCollector;

// 注册事件处理方法
CalleeCollector::addCallee(
    [UserService::class, 'createUser'],
    new Callee(Action::Create)
);

// 注册异步事件处理方法
CalleeCollector::addCallee(
    [UserService::class, 'updateUser'],
    new Callee(Action::Update, async: true)
);
```

### 3. 调用事件处理方法

```php
<?php

use App\Event\Action;
use function Callee\call;

// 调用同步事件处理方法
$result = call(Action::Create, ['name' => 'John', 'email' => 'john@example.com']);
echo "创建用户结果: " . json_encode($result) . "\n";

// 调用异步事件处理方法
call(Action::Update, ['id' => 1, 'data' => ['name' => 'John Doe']]);
echo "更新用户任务已提交\n";
```

## 高级使用

### 作用域管理

你可以为事件处理方法指定作用域，以便在不同的上下文环境中使用不同的处理方法：

```php
<?php

use App\Event\Action;
use App\Service\UserService;
use Callee\Annotation\Callee;

class AdminUserService extends UserService
{
    #[Callee(Action::Create, scope: 'admin')]
    public function createAdminUser(string $name, string $email): array
    {
        // 处理创建管理员用户的逻辑
        return ['id' => 1, 'name' => $name, 'email' => $email, 'role' => 'admin'];
    }
}

// 调用特定作用域的事件处理方法
use function Callee\call;

$result = call(Action::Create, ['name' => 'Admin', 'email' => 'admin@example.com'], scope: 'admin');
echo "创建管理员用户结果: " . json_encode($result) . "\n";
```

### 优先级管理

你可以为事件处理方法设置优先级，以便控制多个处理方法的执行顺序：

```php
<?php

use App\Event\Action;
use Callee\Annotation\Callee;

class LoggingService
{
    #[Callee(Action::Create, priority: 10)]
    public function logBeforeCreate(string $name, string $email): void
    {
        echo "开始创建用户: $name\n";
    }
}

class UserService
{
    #[Callee(Action::Create, priority: 5)]
    public function createUser(string $name, string $email): array
    {
        // 处理创建用户的逻辑
        return ['id' => 1, 'name' => $name, 'email' => $email];
    }
}

class NotificationService
{
    #[Callee(Action::Create, priority: 1)]
    public function notifyUserCreated(string $name, string $email): void
    {
        echo "用户 $name 创建成功，已发送通知\n";
    }
}
```

### 事务支持

你可以使用 `afterCommit` 参数来指定事件处理方法在事务提交后执行：

```php
<?php

use App\Event\Action;
use Callee\Annotation\Callee;

class UserService
{
    #[Callee(Action::Create, afterCommit: true)]
    public function createUser(string $name, string $email): array
    {
        // 处理创建用户的逻辑
        return ['id' => 1, 'name' => $name, 'email' => $email];
    }
}
```

## API 参考

### 注解参数

- `event`: 事件枚举或事件数组，用于触发该方法的调用
- `scope`: 作用域，可选参数，用于限制该注解的作用范围
- `priority`: 优先级，用于决定多个 Callee 在同一事件中被调用的顺序
- `afterCommit`: 是否在事务成功提交后才执行该方法
- `async`: 是否异步执行该方法

### 函数

- `call(CalleeEvent|array|null $event, array $args = [], bool $strict = false, mixed $default = null, ?string $scope = null): mixed`: 调用事件处理方法

### 收集器方法

- `CalleeCollector::addCallee(array $callable, Callee $callee): void`: 添加被调用方法到容器
- `CalleeCollector::hasCallee(CalleeEvent|array|null $event, ?string $scope = null): bool`: 检查是否存在对应的调用方法
- `CalleeCollector::getCallee(CalleeEvent|array|null $event, ?string $scope = null): ?SplPriorityQueue`: 获取调用方法及参数映射
- `CalleeCollector::clear(?string $key = null): void`: 清除容器数据

## 完整代码示例

```php
<?php

namespace App;

use Callee\Annotation\Callee;
use Callee\CalleeEvent;
use Callee\CalleeEventTrait;
use function Callee\call;

enum OrderEvent: string implements CalleeEvent
{
    use CalleeEventTrait;

    case Create = 'order.create';
    case Pay = 'order.pay';
    case Ship = 'order.ship';
    case Complete = 'order.complete';
}

class OrderService
{
    #[Callee(OrderEvent::Create)]
    public function createOrder(array $items, string $userId): array
    {
        // 创建订单逻辑
        $orderId = uniqid();
        return ['id' => $orderId, 'items' => $items, 'userId' => $userId, 'status' => 'created'];
    }

    #[Callee(OrderEvent::Pay, async: true)]
    public function payOrder(string $orderId, string $paymentMethod): bool
    {
        // 支付订单逻辑
        return true;
    }

    #[Callee(OrderEvent::Ship, priority: 5)]
    public function shipOrder(string $orderId, string $address): bool
    {
        // 发货逻辑
        return true;
    }

    #[Callee(OrderEvent::Complete, afterCommit: true)]
    public function completeOrder(string $orderId): bool
    {
        // 完成订单逻辑
        return true;
    }
}

// 使用示例
$order = call(OrderEvent::Create, [
    'items' => [['id' => 1, 'quantity' => 2], ['id' => 2, 'quantity' => 1]],
    'userId' => 'user123'
]);
echo "订单创建成功: " . $order['id'] . "\n";

call(OrderEvent::Pay, [
    'orderId' => $order['id'],
    'paymentMethod' => 'credit_card'
]);
echo "订单支付中...\n";

call(OrderEvent::Ship, [
    'orderId' => $order['id'],
    'address' => '北京市朝阳区'
]);
echo "订单发货中...\n";

call(OrderEvent::Complete, [
    'orderId' => $order['id']
]);
echo "订单完成\n";
```

## 注意事项

1. 确保在使用 `call()` 函数之前，已经设置了 Hyperf 的应用容器
2. 对于异步事件处理方法，需要确保 Hyperf 的任务队列组件已正确配置
3. 对于事务支持，需要确保在事务环境中使用 `afterCommit` 参数
4. 优先级值越大，执行顺序越靠前

## 贡献

我们非常欢迎任何形式的贡献！如果你发现了问题或者有改进的想法，请先查看我们的 [贡献指南](https://github.com/woweijun123/callee/blob/main/CONTRIBUTING.md)。对于新功能的提案或错误修复，请直接提交 Pull
Request。

- **Issues**: <https://github.com/woweijun123/callee/issues>
- **Pull Requests**: <https://github.com/woweijun123/callee/pulls>
