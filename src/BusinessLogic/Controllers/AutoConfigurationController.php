<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Exceptions\BaseException;
use Logeecom\Infrastructure\Http\AutoConfiguration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerConfigInterface;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServicesOrchestratorInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;

/**
 * Class AutoConfigurationController.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class AutoConfigurationController
{
    /**
     * @var UpdateShippingServicesOrchestratorInterface
     */
    private $orchestrator;

    /**
     * @var UpdateShippingServiceTaskStatusServiceInterface $service
     */
    private $service;

    public function __construct(
        UpdateShippingServicesOrchestratorInterface $orchestrator,
        UpdateShippingServiceTaskStatusServiceInterface $service
    )
    {
        $this->orchestrator = $orchestrator;
        $this->service = $service;
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

        /**
         * @var TaskRunnerConfigInterface $taskRunnerConfig
         */
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
        $context = $configService->getContext();

        /** @var UpdateShippingServiceTaskStatus|null $entity */
        $entity = $this->service->getLatestByContext($context);

        if ($entity) {
            $currentStatus = $entity->getStatus();

            if (in_array($currentStatus, [TaskStatus::CREATED, TaskStatus::IN_PROGRESS, TaskStatus::PENDING,
                TaskStatus::RUNNING], true)) {
                $this->service->upsertStatus(
                    $context,
                    TaskStatus::FAILED,
                    'Previous update attempt was reset.',
                    true
                );
            }
        }

        try {
            $this->orchestrator->enqueue($context);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
