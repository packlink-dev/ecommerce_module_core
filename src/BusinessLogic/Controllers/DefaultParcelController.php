<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\QueueService;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
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
     * @throws \Exception
     */
    public function setDefaultParcel(array $rawData)
    {
        $rawData['default'] = true;
        $oldParcel = $this->getConfigService()->getDefaultParcel();

        try {
            $parcelInfo = ParcelInfo::fromArray($rawData);
            $this->getConfigService()->setDefaultParcel($parcelInfo);
        } catch (FrontDtoValidationException $e) {
            // TODO: Change when error handling mechanism is done.
            throw new \Exception('Validation failed');
        }

        if ($oldParcel === null || ($oldParcel !== null && array_diff($oldParcel->toArray(), $parcelInfo->toArray()))) {
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
