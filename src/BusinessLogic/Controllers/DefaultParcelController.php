<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Tasks\UpdateShippingServicesTask;
use Packlink\DemoUI\Services\BusinessLogic\ConfigurationService;

class DefaultParcelController
{
    /**
     * @var ConfigurationService
     */
    private $configService;

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
            /** @var QueueService $queueService */
            $queueService = ServiceRegister::getService(QueueService::CLASS_NAME);
            $defaultQueueName = $this->getConfigService()->getDefaultQueueName();

            $queueService->enqueue(
                $defaultQueueName,
                new UpdateShippingServicesTask(),
                $this->getConfigService()->getContext()
            );
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
