<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\ConfigEntity;
use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\TaskExecution\RunnerStatusStorage;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;
use Logeecom\Tests\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;

class TaskRunnerStatusStorageTest extends TestCase
{
    /** @var TestShopConfiguration */
    private $configuration;

    /**
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     */
    protected function setUp()
    {
        $configuration = new TestShopConfiguration();

        new TestServiceRegister(
            array(
                TimeProvider::CLASS_NAME => function () {
                    return new TestTimeProvider();
                },
                DefaultLoggerAdapter::CLASS_NAME => function () {
                    return new TestDefaultLogger();
                },
                ShopLoggerAdapter::CLASS_NAME => function () {
                    return new TestShopLogger();
                },
                Configuration::CLASS_NAME => function () use ($configuration) {
                    return $configuration;
                },
            )
        );

        $this->configuration = $configuration;

        RepositoryRegistry::registerRepository(ConfigEntity::CLASS_NAME, MemoryRepository::getClassName());
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function testSetTaskRunnerWhenItNotExist()
    {
        $taskRunnerStatusStorage = new RunnerStatusStorage();
        $taskStatus = new TaskRunnerStatus('guid', 123456789);

        $taskRunnerStatusStorage->setStatus($taskStatus);
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function testSetTaskRunnerWhenItExist()
    {
        $taskRunnerStatusStorage = new RunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid', 123456789);

        try {
            $taskRunnerStatusStorage->setStatus($taskStatus);
        } catch (\Exception $ex) {
            $this->fail('Set task runner status storage should not throw exception.');
        }
    }

    /**
     * @expectedException \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function testSetTaskRunnerWhenItExistButItIsNotTheSame()
    {
        $taskRunnerStatusStorage = new RunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid2', 123456789);

        $taskRunnerStatusStorage->setStatus($taskStatus);
    }
}
