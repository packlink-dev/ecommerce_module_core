<?php

namespace Logeecom\Tests\Infrastructure\TaskExecution;

use Logeecom\Tests\Common\TestComponents\Logger\TestDefaultLogger;
use Logeecom\Tests\Common\TestServiceRegister;
use PHPUnit\Framework\TestCase;
use Logeecom\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Logeecom\Infrastructure\Configuration;
use Logeecom\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatus;
use Logeecom\Infrastructure\TaskExecution\TaskRunnerStatusStorage;
use Logeecom\Infrastructure\Utility\TimeProvider;
use Logeecom\Tests\Common\TestComponents\TestShopConfiguration;
use Logeecom\Tests\Common\TestComponents\Logger\TestShopLogger;
use Logeecom\Tests\Common\TestComponents\Utility\TestTimeProvider;

class TaskRunnerStatusStorageTest extends TestCase
{
    /** @var TestShopConfiguration */
    private $configuration;

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
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function testSetTaskRunnerWhenItNotExist()
    {
        $taskRunnerStatusStorage = new TaskRunnerStatusStorage();
        $taskStatus = new TaskRunnerStatus('guid', 123456789);

        $this->expectException(
            'Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException'
        );
        $taskRunnerStatusStorage->setStatus($taskStatus);
    }

    public function testSetTaskRunnerWhenItExist()
    {
        $taskRunnerStatusStorage = new TaskRunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid', 123456789);

        try {
            $taskRunnerStatusStorage->setStatus($taskStatus);
        } catch (\Exception $ex) {
            $this->fail('Set task runner status storage should not throw exception.');
        }
    }

    /**
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function testSetTaskRunnerWhenItExistButItIsNotTheSame()
    {
        $taskRunnerStatusStorage = new TaskRunnerStatusStorage();
        $this->configuration->setTaskRunnerStatus('guid', 123456789);
        $taskStatus = new TaskRunnerStatus('guid2', 123456789);

        $this->expectException('Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException');
        $taskRunnerStatusStorage->setStatus($taskStatus);
    }
}
