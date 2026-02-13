<?php

namespace Packlink\BusinessLogic\Tasks\BusinessTasks;

use Generator;
use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Interfaces\Priority;
use Logeecom\Infrastructure\TaskExecution\Model\TaskStatus;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Country\WarehouseCountryService;
use Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\Tasks\Interfaces\BusinessTask;
use Packlink\BusinessLogic\Tasks\TaskExecutionConfig;
use Packlink\BusinessLogic\UpdateShippingServices\Interfaces\UpdateShippingServiceTaskStatusServiceInterface;
use Packlink\BusinessLogic\UpdateShippingServices\Models\UpdateShippingServiceTaskStatus;
use Packlink\BusinessLogic\UpdateShippingServices\UpdateShippingServiceTaskStatusService;

class UpdateShippingServicesBusinessTask implements BusinessTask
{
    /**
     * Tag for special services
     */
    const SPECIAL_SERVICE_TAG = 'EXCLUSIVE_FOR_PLUS';

    /**
     * WarehouseCountryService instance.
     *
     * @var WarehouseCountryService
     */
    private $countryService;

    /**
     * Optional execution config override.
     *
     * @var TaskExecutionConfig|null
     */
    private $executionConfig;

    /**
     * @var UpdateShippingServiceTaskStatusService
     */
    private $statusService;

    public function __construct(TaskExecutionConfig $executionConfig = null)
    {
        $this->executionConfig = $executionConfig;
    }

    /**
     * Gets all local methods and remote services and synchronizes data.
     *
     * Uses yield for progress tracking.
     *
     * @return Generator
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws FrontDtoValidationException|QueryFilterInvalidParamException
     */
    public function execute(): \Generator
    {
        yield 1;

        $context = $this->getContext();

        try {
            $this->getStatusService()->upsertStatus($context, TaskStatus::IN_PROGRESS);

            if ($this->shouldExecute()) {
                $apiServices = $this->getRemoteServices();
                $apiSpecialServices = $this->getSpecialServices($apiServices);

                $currentMethods = $this->getShippingMethodService()->getAllMethods();
                $currentSpecialMethods = $this->getSpecialServices($currentMethods);

                yield 20;
                yield from $this->syncServices($currentMethods, $apiServices);
                yield from $this->syncServicesSpecial($currentSpecialMethods, $apiSpecialServices);
            }

            $this->getStatusService()->upsertStatus($context, TaskStatus::COMPLETED, null, true);

            yield 100;
        } catch (\Exception $e) {
            $this->getStatusService()->upsertStatus($context, TaskStatus::FAILED, $e->getMessage(), true);

            throw $e;
        }
    }

    /**
     * Gets all available services for current user.
     *
     * @return array Key is service Id and value is @see \Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails object.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    protected function getRemoteServices(): array
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        $warehouse = $config->getDefaultWarehouse();

        if ($warehouse !== null) {
            $sourceCountryCode = $warehouse->country;
        } else {
            $sourceCountryCode = $config->getUserInfo()->country;
        }

        $supportedCountries = $this->getCountryService()->getSupportedCountries();
        $sourceCountry = array_key_exists($sourceCountryCode, $supportedCountries) ? $supportedCountries[$sourceCountryCode] : null;
        if (!$sourceCountry) {
            return array();
        }

        $parcel = $config->getDefaultParcel() ?: ParcelInfo::defaultParcel();
        $package = Package::fromArray($parcel->toArray());

        $allServices = array();
        foreach ($supportedCountries as $country) {
            $this->setServices(
                $allServices,
                new ShippingServiceSearch(
                    null,
                    $sourceCountry->code,
                    $sourceCountry->postalCode,
                    $country->code,
                    $country->postalCode,
                    array($package)
                )
            );
        }

        return $allServices;
    }

    /**
     * Sets shipping services from Packlink API.
     *
     * @param array $allServices Key is service ID, value is an array with keys 'service' and 'serviceDetails'.
     * @param ShippingServiceSearch $searchParams Details for which to search for services.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function setServices(array &$allServices, ShippingServiceSearch $searchParams)
    {
        $proxy = $this->getProxy();
        $serviceDeliveryDetails = $proxy->getShippingServicesDeliveryDetails($searchParams);
        foreach ($serviceDeliveryDetails as $deliveryDetail) {
            $allServices[] = $deliveryDetail;
        }
    }

    /**
     * Creates, updates or deletes local shipping methods based on state on Packlink API.
     *
     * @param array $currentMethods Current shipping methods in shop.
     * @param array $apiServices Services retrieved from API.
     *
     * @return \Generator
     */
    protected function syncServices(array $currentMethods, array $apiServices): Generator
    {
        $progress = 20;
        $progressStep = count($currentMethods) > 0 ? (40 / count($currentMethods)) : 40;

        foreach ($currentMethods as $shippingMethod) {
            $this->updateShippingMethod($shippingMethod, $apiServices);

            $progress += $progressStep;
            yield $progress;
        }

        yield 60;
        $batch = 0;
        foreach ($apiServices as $service) {
            $batch++;
            if ($batch === 20) {
                yield; // Keep-alive
                $batch = 0;
            }
            $this->getShippingMethodService()->add($service);
        }
    }

    /**
     * Creates, updates or deletes local shipping methods based on state on Packlink API.
     *
     * @param array $currentMethods Current shipping methods in shop.
     * @param array $apiServices Services retrieved from API.
     *
     * @return \Generator
     */
    protected function syncServicesSpecial(array $currentMethods, array $apiServices): Generator
    {
        $progress = 60;
        $progressStep = count($currentMethods) > 0 ? (20 / count($currentMethods)) : 20;

        foreach ($currentMethods as $shippingMethod) {
            $this->updateShippingMethod($shippingMethod, $apiServices, true);

            $progress += $progressStep;
            yield $progress;
        }

        yield 80;
        $batch = 0;
        foreach ($apiServices as $service) {
            $batch++;
            if ($batch === 20) {
                yield; // Keep-alive
                $batch = 0;
            }
            $this->getShippingMethodService()->add($service, true);
        }
    }

    /**
     * Updates shipping method from data from Packlink API or deletes it if service does not exist.
     *
     * @param ShippingMethod $shippingMethod Local shipping method.
     * @param ShippingServiceDetails[] $apiServices Shipping services returned from API.
     */
    protected function updateShippingMethod(ShippingMethod $shippingMethod, array &$apiServices, $special = false)
    {
        $shippingServices = array();
        foreach ($apiServices as $service) {
            if ($this->serviceBelongsToMethod($service, $shippingMethod, $special)) {
                $shippingServices[] = ShippingService::fromServiceDetails($service);
            }
        }

        if (!empty($shippingServices)) {
            $shippingMethod->setShippingServices($shippingServices);
            $this->getShippingMethodService()->save($shippingMethod);

            /** @var ShippingService $service */
            foreach ($shippingServices as $service) {
                unset($apiServices[$service->serviceId]);
            }

            return;
        }

        $this->getShippingMethodService()->delete($shippingMethod);
    }

    /**
     * Returns all special services from an API, and removes it from an array
     *
     * @param array $apiServices
     *
     * @return array
     */
    protected function getSpecialServices(array &$apiServices): array
    {
        $specialServices = array();

        foreach ($apiServices as $key => $service) {
            if (in_array(array('id' => self::SPECIAL_SERVICE_TAG), $service->tags, true)) {
                $specialServices[] = $service;
                unset($apiServices[$key]);
            }
        }

        return $specialServices;
    }

    /**
     * Checks if task should be executed.
     *
     * @return bool TRUE if task should execute; otherwise, FALSE.
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    protected function shouldExecute(): bool
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);
        $userInfo = $config->getUserInfo();

        if ($userInfo === null) {
            return false;
        }

        return $config->getDefaultWarehouse() !== null
            || $this->getCountryService()->isCountrySupported($userInfo->country);
    }

    /**
     * Gets instance of shipping method service.
     *
     * @return ShippingMethodService Shipping method service.
     */
    protected function getShippingMethodService(): ShippingMethodService
    {
        /** @var ShippingMethodService $shippingMethodService */
        $shippingMethodService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);

        return $shippingMethodService;
    }

    /**
     * Gets instance of Packlink proxy.
     *
     * @return Proxy Proxy instance.
     */
    protected function getProxy(): Proxy
    {
        /** @var Proxy $proxy */
        /** @noinspection OneTimeUseVariablesInspection */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);

        return $proxy;
    }

    /**
     * Checks if given shipping service belongs to given shipping method.
     *
     * @param \Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails $service Shipping service from API.
     *
     * @param \Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod $shippingMethod Shipping method from system.
     *
     * @return bool TRUE if given shipping service belongs to given shipping method; otherwise, FALSE.
     */
    protected function serviceBelongsToMethod(ShippingServiceDetails $service, ShippingMethod $shippingMethod, $special = false): bool
    {
        $carrierName = $special ? $service->carrierName . ' ' . $service->serviceName : $service->carrierName;

        return $carrierName === $shippingMethod->getCarrierName()
            && $service->national === $shippingMethod->isNational()
            && $service->expressDelivery === $shippingMethod->isExpressDelivery()
            && $service->departureDropOff === $shippingMethod->isDepartureDropOff()
            && $service->destinationDropOff === $shippingMethod->isDestinationDropOff()
            && $service->currency === $shippingMethod->getCurrency();
    }

    /**
     * Returns an instance of country service.
     *
     * @return WarehouseCountryService
     */
    protected function getCountryService(): WarehouseCountryService
    {
        if ($this->countryService === null) {
            $this->countryService = ServiceRegister::getService(WarehouseCountryService::CLASS_NAME);
        }

        return $this->countryService;
    }

    /**
     * Get task priority.
     *
     * @return int Priority (0-100).
     */
    public function getPriority(): int
    {
        return Priority::NORMAL;
    }

    /**
     * Serialize task to array.
     *
     * @return array Task data.
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->executionConfig !== null) {
            $data['execution_config'] = $this->executionConfig->toArray();
        }

        return $data;
    }

    /**
     * Deserialize task from array.
     *
     * @param array $data Task data.
     *
     * @return static Task instance.
     */
    public static function fromArray(array $data): BusinessTask
    {
        $executionConfig = null;

        if (!empty($data['execution_config']) && is_array($data['execution_config'])) {
            $executionConfig = TaskExecutionConfig::fromArray($data['execution_config']);
        }

        return new static($executionConfig);
    }

    /**
     * @return TaskExecutionConfig|null
     */
    public function getExecutionConfig()
    {
        return $this->executionConfig;
    }
    /**
     * @return string
     */
    protected function getContext()
    {
        if ($this->executionConfig !== null && method_exists($this->executionConfig, 'getContext')) {
            return (string)$this->executionConfig->getContext();
        }

        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        if (method_exists($config, 'getContext')) {
            return (string)$config->getContext();
        }

        return '';
    }


    /**
     * @return UpdateShippingServiceTaskStatusServiceInterface
     */
    protected function getStatusService()
    {
        if ($this->statusService === null) {
            /**
             * @var UpdateShippingServiceTaskStatusServiceInterface $statusService
             */
            $statusService = ServiceRegister::getService(UpdateShippingServiceTaskStatusServiceInterface::class);

            $this->statusService = $statusService;
        }

        return $this->statusService;
    }
}