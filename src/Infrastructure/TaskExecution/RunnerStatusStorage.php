<?php

namespace Logeecom\Infrastructure\TaskExecution;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskRunnerStatusStorage;

/**
 * Class RunnerStatusStorage.
 *
 * @package Logeecom\Infrastructure\TaskExecution
 */
class RunnerStatusStorage implements TaskRunnerStatusStorage
{
    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * Returns task runner status.
     *
     * @return TaskRunnerStatus Task runner status instance.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function getStatus()
    {
        $result = $this->getConfigService()->getTaskRunnerStatus();
        if (empty($result)) {
            throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available');
        }

        return new TaskRunnerStatus($result['guid'], $result['timestamp']);
    }

    /**
     * Sets task runner status.
     *
     * @param TaskRunnerStatus $status Status instance.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    public function setStatus(TaskRunnerStatus $status)
    {
        $this->checkTaskRunnerStatusChangeAvailability($status);
        $this->getConfigService()->setTaskRunnerStatus($status->getGuid(), $status->getAliveSinceTimestamp());
    }

    /**
     * Checks if task runner can change availability status.
     *
     * @param TaskRunnerStatus $status Status instance.
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function checkTaskRunnerStatusChangeAvailability(TaskRunnerStatus $status)
    {
        $currentGuid = $this->getStatus()->getGuid();
        $guidForUpdate = $status->getGuid();

        if (!empty($currentGuid) && !empty($guidForUpdate) && $currentGuid !== $guidForUpdate) {
            throw new TaskRunnerStatusChangeException(
                'Task runner with guid: ' . $guidForUpdate . ' can not change the status.'
            );
        }
    }

    /**
     * Gets instance of @see Configuration service.
     *
     * @return Configuration Service instance.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
