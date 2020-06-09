<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\AsyncBatchStarter;
use Logeecom\Infrastructure\TaskExecution\Interfaces\AsyncProcessService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\FakeRunnable;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestAsyncProcessStarter;
use PHPUnit\Framework\TestCase;

class AsyncBatchStarterTest extends TestCase
{
    private $asyncProcessStarter;

    protected function setUp()
    {
        $asyncProcessStarter = new TestAsyncProcessStarter(true);

        ServiceRegister::registerService(
            AsyncProcessService::CLASS_NAME,
            function () use($asyncProcessStarter) {
                return $asyncProcessStarter;
            }
        );

        ServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new NativeSerializer();
            }
        );


        $this->asyncProcessStarter = $asyncProcessStarter;
    }

    public function testCreationWhenItemCountIsLessThanBatchSize()
    {
        $batchStarter = new AsyncBatchStarter(2, array(new FakeRunnable()));

        $this->assertSame(0, $batchStarter->getMaxNestingLevels(), 'AsyncBatchStarter should create sub-levels only when batch size is exceeded.');
    }

    public function testCreationWhenItemCountIsGreaterThanBatchSize()
    {
        $batchStarter = new AsyncBatchStarter(2, array(new FakeRunnable(), new FakeRunnable(), new FakeRunnable()));

        $this->assertSame(1, $batchStarter->getMaxNestingLevels(), 'AsyncBatchStarter should create sub-levels when batch size is exceeded.');
    }

    public function testCreationWhenItemCountIsMuchGreaterThanBatchSize()
    {
        $batchStarter = new AsyncBatchStarter(
            2,
            array(
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable()
            )
        );

        $this->assertSame(2, $batchStarter->getMaxNestingLevels(), 'AsyncBatchStarter should create sub-sub-levels when batch size is exceeded.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     */
    public function testRunningBatchWithoutNestingLevel()
    {
        $testRunner = new FakeRunnable();
        $batchStarter = new AsyncBatchStarter(2, array($testRunner));

        $batchStarter->run();

        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(1, $startCallHistory, 'AsyncBatchStarter should start runners immediately when there are no sub-batches.');
        $this->assertEquals($testRunner, $startCallHistory[0]['runner'], 'AsyncBatchStarter should start runners immediately when there are no sub-batches.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     */
    public function testRunningBatchWithNestingLevel()
    {
        $testRunner = new FakeRunnable();
        $batchStarter = new AsyncBatchStarter(2, array(new FakeRunnable(), new FakeRunnable(), $testRunner));

        $batchStarter->run();

        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(4, $startCallHistory, 'AsyncBatchStarter should start sub-batches.');
        $this->assertEquals($testRunner, $startCallHistory[3]['runner'], 'AsyncBatchStarter should start sub-batches.');
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     */
    public function testRunningBatchAfterSerialization()
    {
        $testRunnerProperty = 'serialization running test';
        $testRunner = new FakeRunnable($testRunnerProperty);
        $batchStarter = new AsyncBatchStarter(2, array(new FakeRunnable(), new FakeRunnable(), $testRunner));
        /** @var AsyncBatchStarter $batchStarter */
        $batchStarter = Serializer::unserialize(Serializer::serialize($batchStarter));

        $batchStarter->run();

        $startCallHistory = $this->asyncProcessStarter->getMethodCallHistory('start');
        $this->assertCount(4, $startCallHistory, 'AsyncBatchStarter should be serializable.');
        /** @var FakeRunnable $runner */
        $runner = $startCallHistory[3]['runner'];
        $this->assertEquals($testRunnerProperty, $runner->getTestProperty(), 'AsyncBatchStarter should be serializable.');
    }

    public function testBatchToString()
    {
        $batchStarter1 = new AsyncBatchStarter(2, array(new FakeRunnable(), new FakeRunnable(), new FakeRunnable()));
        $batchStarter2 = new AsyncBatchStarter(
            2,
            array(
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable()
            )
        );
        $batchStarter3 = new AsyncBatchStarter(
            3,
            array(
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable()
            )
        );

        $this->assertSame('B(B(R, R), R)', (string)$batchStarter1);
        $this->assertSame(1, $batchStarter1->getMaxNestingLevels());
        $this->assertSame('B(B(B(R, R), B(R, R), R), B(B(R, R), R, R), R, R)', (string)$batchStarter2);
        $this->assertSame(2, $batchStarter2->getMaxNestingLevels());
        $this->assertSame('B(B(R, R, R), B(R, R, R), B(R, R, R), R, R)', (string)$batchStarter3);
        $this->assertSame(1, $batchStarter3->getMaxNestingLevels());
    }

    public function testWaitTimeCalculation()
    {
        $requestTimeout = 1;
        $batchStarter1 = new AsyncBatchStarter(2, array(new FakeRunnable(), new FakeRunnable(), new FakeRunnable()));
        $batchStarter2 = new AsyncBatchStarter(
            2,
            array(
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable()
            )
        );
        $batchStarter3 = new AsyncBatchStarter(
            3,
            array(
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable(), new FakeRunnable(), new FakeRunnable(),
                new FakeRunnable()
            )
        );

        $this->assertSame('B(B(R, R), R)', (string)$batchStarter1);
        $this->assertSame(1, $batchStarter1->getMaxNestingLevels());
        $this->assertSame(1, $batchStarter1->getWaitTime($requestTimeout), 'Wait time should be calculated as batchSize * maxNestingLevel * requestTimeout - runners out of batch * requestTimeout');
        $this->assertSame('B(B(B(R, R), B(R, R), R), B(B(R, R), R, R), R, R)', (string)$batchStarter2);
        $this->assertSame(2, $batchStarter2->getMaxNestingLevels());
        $this->assertSame(2, $batchStarter2->getWaitTime($requestTimeout), 'Wait time should be calculated as batchSize * maxNestingLevel * requestTimeout - runners out of batch * requestTimeout');
        $this->assertSame('B(B(R, R, R), B(R, R, R), B(R, R, R), R)', (string)$batchStarter3);
        $this->assertSame(1, $batchStarter3->getMaxNestingLevels());
        $this->assertSame(2, $batchStarter3->getWaitTime($requestTimeout), 'Wait time should be calculated as batchSize * maxNestingLevel * requestTimeout - runners out of batch * requestTimeout');
    }
}