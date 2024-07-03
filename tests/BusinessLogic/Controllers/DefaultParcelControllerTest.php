<?php

namespace Logeecom\Tests\BusinessLogic\Controllers;

use DateTime;
use Exception;
use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\Serializer\Concrete\NativeSerializer;
use Logeecom\Infrastructure\Serializer\Serializer;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerWakeup;
use Logeecom\Infrastructure\TaskExecution\QueueItem;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Logeecom\Infrastructure\Utility\Events\EventBus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryQueueItemRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\TestRepositoryRegistry;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestQueueService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TaskExecution\TestTaskRunnerWakeupService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Controllers\DefaultParcelController;
use Packlink\BusinessLogic\DTO\ValidationError;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Scheduler\Models\Schedule;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class DashboardControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Controllers
 */
class DefaultParcelControllerTest extends BaseTestWithServices
{
    /**
     * @var DefaultParcelController
     */
    private $defaultParcelController;

    /**
     * @before
     * @inheritdoc
     */
    public function before()
    {
        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
        TestFrontDtoFactory::register(ValidationError::CLASS_KEY, ValidationError::CLASS_NAME);
        TestFrontDtoFactory::register(Warehouse::CLASS_KEY, Warehouse::CLASS_NAME);
        TestFrontDtoFactory::register(ParcelInfo::CLASS_KEY, ParcelInfo::CLASS_NAME);
        TestRepositoryRegistry::registerRepository(QueueItem::CLASS_NAME, MemoryQueueItemRepository::getClassName());
        TestRepositoryRegistry::registerRepository(Schedule::CLASS_NAME, MemoryRepository::getClassName());

        $queue = new TestQueueService();
        $taskRunnerStarter = new TestTaskRunnerWakeupService();
        $configuration = new TestShopConfiguration();
        $nowDateTime = new DateTime('2018-03-21T13:42:05');

        $timeProvider = new TestTimeProvider();
        $timeProvider->setCurrentLocalTime($nowDateTime);

        TestServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () use ($configuration) {
                return $configuration;
            }
        );

        TestServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () use ($timeProvider) {
                return $timeProvider;
            }
        );

        TestServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );

        TestServiceRegister::registerService(
            Serializer::CLASS_NAME,
            function () {
                return new NativeSerializer();
            }
        );

        TestServiceRegister::registerService(
            QueueService::CLASS_NAME,
            function () use ($queue) {
                return $queue;
            }
        );

        TestServiceRegister::registerService(
            TaskRunnerWakeup::CLASS_NAME,
            function () use ($taskRunnerStarter) {
                return $taskRunnerStarter;
            }
        );
        $this->defaultParcelController = new DefaultParcelController();
    }

    /**
     * Tests the case when auth key is not set.
     */
    public function testDefaultParcelGet()
    {
        $this->defaultParcelController->setDefaultParcel(
            array(
                'weight' => 10,
                'width' => 10,
                'length' => 10,
                'height' => 10,
            )
        );

        $result = $this->defaultParcelController->getDefaultParcel();

        $this->assertEquals(10, $result->weight);
        $this->assertEquals(10, $result->width);
        $this->assertEquals(10, $result->length);
        $this->assertEquals(10, $result->height);
    }

    /**
     * Tests the case when auth key is set and default warehouse is not set.
     */
    public function testDefaultParcelSetValid()
    {
        $exceptionThrown = false;

        try {
            $this->defaultParcelController->setDefaultParcel(
                array(
                    'weight' => 10,
                    'width' => 10,
                    'length' => 10,
                    'height' => 10,
                )
            );
        } catch (Exception $ex) {
            $exceptionThrown = true;
        }

        $this->assertNotTrue($exceptionThrown);
    }

    /**
     * Tests the case when auth key is set and default warehouse is not set.
     */
    public function testDefaultParcelSetInvalid()
    {
        $exceptionThrown = false;

        try {
            $this->defaultParcelController->setDefaultParcel(
                array(
                    'weight' => 'asdasds',
                    'width' => 10,
                    'length' => 10,
                    'height' => 10,
                )
            );
        } catch (Exception $ex) {
            $exceptionThrown = true;
        }

        $this->assertTrue($exceptionThrown);
    }
}
