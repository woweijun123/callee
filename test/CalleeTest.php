<?php

namespace Test;

use Callee\Annotation\Callee;
use Callee\CalleeCollector;
use Callee\CalleeData;
use Callee\CalleeEvent;
use Callee\CalleeEventTrait;
use Callee\Reflection;
use Hyperf\Context\ApplicationContext;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSource;
use PHPUnit\Framework\TestCase;

class CalleeTest extends TestCase
{
    public static function tearDownAfterClass(): void
    {
        CalleeCollector::clear();
        parent::tearDownAfterClass();
    }

    /**
     * @return CalleeData[]
     */
    private static function calleeQueueToArray(CalleeEvent|array $event, ?string $scope = null): array
    {
        $queue = CalleeCollector::getCallee($event, $scope);

        return $queue?->toArray() ?? [];
    }

    private static function firstCalleeData(CalleeEvent|array $event, ?string $scope = null): ?CalleeData
    {
        $all = self::calleeQueueToArray($event, $scope);

        return $all[0] ?? null;
    }

    public function testAddCallee(): void
    {
        CalleeCollector::clear();
        CalleeCollector::addCallee(
            [CalleeTestCallee::class, 'buriedPoint'],
            new Callee(CalleeEventEnum::user, async: false)
        );

        (new Callee(CalleeEventEnum::group))->collectMethod(self::class, 'onGroup');
        (new Callee(CalleeEventEnumString::list))->collectMethod(self::class, 'onList');

        (new Callee(CalleeEventEnum::group, self::class))->collectMethod(self::class, 'onGroup');
        (new Callee(CalleeEventEnumString::list, self::class))->collectMethod(self::class, 'onList');

        CalleeCollector::addCallee([self::class, 'stubAdd'], new Callee(CalleeEventEnumString::add));
        CalleeCollector::addCallee([self::class, 'stubAdd'], new Callee(CalleeEventEnumString::add));
        CalleeCollector::addCallee([self::class, 'onDel'], new Callee(CalleeEventEnumString::delete));

        $this->assertNotNull(CalleeCollector::getCallee(CalleeEventEnum::user));
        $this->assertNotNull(CalleeCollector::getCallee(CalleeEventEnumString::add));
    }

    /**
     * @depends testAddCallee
     */
    public function testGetCallee(): void
    {
        $user = self::firstCalleeData(CalleeEventEnum::user);
        $this->assertNotNull($user);
        $this->assertSame([CalleeTestCallee::class, 'buriedPoint'], $user->callable);

        $add = self::firstCalleeData(CalleeEventEnumString::add);
        $this->assertNotNull($add);
        $this->assertSame([self::class, 'stubAdd'], $add->callable);

        $delete = self::firstCalleeData(CalleeEventEnumString::delete);
        $this->assertNotNull($delete);
        $this->assertSame([self::class, 'onDel'], $delete->callable);

        $scopedGroup = self::firstCalleeData(CalleeEventEnum::group, self::class);
        $this->assertNotNull($scopedGroup);
        $this->assertSame([self::class, 'onGroup'], $scopedGroup->callable);

        $event = 'abc';
        $this->assertNull(CalleeCollector::getCallee(CalleeEventEnumString::tryFrom($event)));
        $this->assertNull(CalleeCollector::getCallee(CalleeEventEnumString::tryFrom($event), self::class));
    }

    public function testInvokeCallee(): void
    {
        $hadContainer = ApplicationContext::hasContainer();
        $previous = $hadContainer ? ApplicationContext::getContainer() : null;

        try {
            CalleeCollector::clear();
            CalleeCollector::addCallee(
                [CalleeTestCallee::class, 'buriedPoint'],
                new Callee(CalleeEventEnum::user, async: false)
            );

            $container = new Container(new DefinitionSource([]));
            $container->set(CalleeTestCallee::class, new CalleeTestCallee());
            ApplicationContext::setContainer($container);

            $result = Reflection::call(CalleeEventEnum::user, ['text' => 'hello', 'id' => '42']);
            $this->assertSame(['hello', '42'], $result);
        } finally {
            if ($hadContainer && $previous !== null) {
                ApplicationContext::setContainer($previous);
            }
        }
    }

    public static function stubAdd(): void
    {
    }

    public function onGroup(): void
    {
    }

    public function onList(): void
    {
    }

    public function onDel(): void
    {
    }
}

enum CalleeEventEnum implements CalleeEvent
{
    use CalleeEventTrait;

    case user;

    case member;

    case group;
}

enum CalleeEventEnumString: string implements CalleeEvent
{
    use CalleeEventTrait;

    case add = 'add';

    case update = 'update';

    case delete = 'delete';

    case list = 'list';
}

class CalleeTestCallee
{
    /**
     * 统一埋点
     *
     * @param string $text
     * @param string $id
     * @return array{0: string, 1: string}
     */
    #[Callee(CalleeEventEnum::user, async: true)]
    public function buriedPoint(string $text, string $id): array
    {
        return [$text, $id];
    }
}
