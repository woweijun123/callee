<?php

namespace Test;

use Callee\Annotation\Callee;
use Callee\CalleeCollector;
use Callee\CalleeEvent;
use Callee\CalleeEventTrait;
use Callee\Reflection;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use PHPUnit\Framework\TestCase;
use function Callee\call;

class CalleeTest extends TestCase
{

    public function setUp(): void
    {
        // 设置容器
        $container = new Container(new DefinitionSource([]));
        $container->set(CalleeTestCallee::class, new CalleeTestCallee());
        $container->set(Reflection::class, new Reflection());
        ApplicationContext::setContainer($container);
    }

    /**
     * 测试添加调用方法
     * @return void
     */
    public function testAddCallee(): void
    {
        $hadContainer = ApplicationContext::hasContainer();
        $previous = $hadContainer ? ApplicationContext::getContainer() : null;

        try {
            CalleeCollector::clear();

            // 方式1：直接添加
            CalleeCollector::addCallee([CalleeTestCallee::class, 'create'], new Callee(Action::Create, async: false));
            CalleeCollector::addCallee([CalleeTestCallee::class, 'delete'], new Callee(Action::Delete, async: false));

            // 方式2：通过注解收集
            (new Callee(Action::Update))->collectMethod(CalleeTestCallee::class, 'update');
            (new Callee(Action::Read))->collectMethod(CalleeTestCallee::class, 'read');

            // 验证添加成功
            $callCreate = call(Action::Create, ['text' => 'hello', 'id' => '123']);
            $this->assertSame(['hello', '123'], $callCreate);

            $callDelete = call(Action::Delete, ['id' => '123']);
            $this->assertSame('delete', $callDelete);

            $callUpdate = call(Action::Update, ['id' => '123']);
            $this->assertSame('update', $callUpdate);

            $callRead = call(Action::Read, ['id' => '123']);
            $this->assertSame('read', $callRead);
        } finally {
            if ($hadContainer && $previous !== null) {
                ApplicationContext::setContainer($previous);
            }
        }
    }

    /**
     * 测试清除调用方法
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        CalleeCollector::clear();
    }
}

enum Action: string implements CalleeEvent
{
    use CalleeEventTrait;

    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
    case Read = 'read';
}

class CalleeTestCallee
{
    /**
     * 测试添加调用方法
     * @return void
     */
    #[Callee(Action::Create)]
    public function create(string $text, string $id): array
    {
        return [$text, $id];
    }

    /**
     * 测试删除调用方法
     * @return void
     */
    #[Callee(Action::Delete)]
    // 测试用的存根方法
    public function delete(): string
    {
        return 'delete';
    }

    /**
     * 测试更新调用方法
     * @return void
     */
    #[Callee(Action::Update)]
    public function update(): string
    {
        return 'update';
    }

    /**
     * 测试读取调用方法
     * @return void
     */
    #[Callee(Action::Read, async: true)]
    public function read(): string
    {
        return 'read';
    }
}
