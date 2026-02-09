<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\Http\AutoConfiguration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskStatusProviderInterface;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;

/**
 * Class AutoConfigurationController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class AutoConfigurationController
{
    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;

    public function __construct(TaskExecutorInterface $taskExecutor)
    {
        $this->taskExecutor = $taskExecutor;
    }
    /**
     * Starts the auto-configuration process.
     *
     * @param bool $enqueueTask Indicates whether to enqueue the update services task after
     *  the successful configuration.
     *
     * @return bool TRUE if the process completed successfully; otherwise, FALSE.
     */
    public function start($enqueueTask = false)
    {
        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        /** @var \Logeecom\Infrastructure\Http\HttpClient $httpService */
        $httpService = ServiceRegister::getService(HttpClient::CLASS_NAME);
        $taskRunnerConfig = ServiceRegister::getService(TaskRunnerConfigInterface::CLASS_NAME);
        $service = new AutoConfiguration($configService, $httpService, $taskRunnerConfig);

        try {
            $success = $service->start();
            if ($success) {
                if ($enqueueTask) {
                    $this->enqueueUpdateServicesTask($configService);
                }
            }
        } catch (BaseException $e) {
            $success = false;
        }

        return $success;
    }

    /**
     * @param Configuration $configService
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryClassException
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     */
    protected function enqueueUpdateServicesTask(Configuration $configService)
    {
        /** @var TaskStatusProviderInterface $statusProvider */
        $statusProvider = ServiceRegister::getService(TaskStatusProviderInterface::class);

        $latest = $statusProvider->getLatestStatus('UpdateShippingServicesBusinessTask', $configService->getContext());

        if (in_array($latest->getStatus(), [TaskStatus::SCHEDULED, TaskStatus::PENDING, TaskStatus::RUNNING], true)) {
            return;
        }

        $this->taskExecutor->enqueue(new UpdateShippingServicesBusinessTask());
    }
}
