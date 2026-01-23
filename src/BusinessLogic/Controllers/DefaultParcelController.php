<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\TaskExecutorInterface;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Tasks\BusinessTasks\UpdateShippingServicesBusinessTask;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

class DefaultParcelController
{
    /**
     * @var ConfigurationService
     */
    private $configService;

    /**
     * @var TaskExecutorInterface
     */
    private $taskExecutor;

    public function __construct(TaskExecutorInterface $taskExecutor)
    {
        $this->taskExecutor = $taskExecutor;
    }

    /**
     * Gets default parcel.
     *
     * @return ParcelInfo|null
     */
    public function getDefaultParcel()
    {
        return $this->getConfigService()->getDefaultParcel();
    }

    /**
     * Sets default parcel and enqueues the Update shipping services task if needed.
     *
     * @param array $rawData
     *
     * @throws \Logeecom\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function setDefaultParcel(array $rawData)
    {
        $rawData['default'] = true;
        $oldParcel = $this->getConfigService()->getDefaultParcel();

        $parcelInfo = ParcelInfo::fromArray($rawData);
        $this->getConfigService()->setDefaultParcel($parcelInfo);

        if ($oldParcel === null || array_diff($oldParcel->toArray(), $parcelInfo->toArray())) {
            $this->taskExecutor->enqueue(new UpdateShippingServicesBusinessTask());
        }
    }

    /**
     * Returns an instance of configuration service.
     *
     * @return ConfigurationService
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
