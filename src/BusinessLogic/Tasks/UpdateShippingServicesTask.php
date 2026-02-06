<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Country\WarehouseCountryService;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\Utility\Php\Php55;

/**
 * Task to update available shipping services and their default costs.
 *
 * @package Packlink\BusinessLogic\Tasks
 */
class UpdateShippingServicesTask extends Task
{
    const SPECIAL_SERVICE_TAG = 'EXCLUSIVE_FOR_PLUS';

    /**
     * @var WarehouseCountryService
     */
    private $countryService;

    /**
     * Transforms array into an serializable object,
     *
     * @param array $array Data that is used to instantiate serializable object.
     *
     * @return \Logeecom\Infrastructure\Serializer\Interfaces\Serializable
     *      Instance of serialized object.
     */
    public static function fromArray(array $array)
    {
        return new static();
    }

    /**
     * Transforms serializable object into an array.
     *
     * @return array Array representation of a serializable object.
     */
    public function toArray()
    {
        return array();
    }

    /**
     * @inheritDoc
     */
    public function __serialize()
    {
        return $this->toArray();
    }

    /**
     * @inheritDoc
     */
    public function __unserialize($data)
    {
    }

    /**
     * Gets all local methods and remote services and synchronizes data.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    public function execute()
    {
        $this->reportProgress(1);

        if ($this->shouldExecute()) {
            $apiServices = $this->getRemoteServices();
            $apiSpecialServices = $this->getSpecialServices($apiServices);

            $currentMethods = $this->getShippingMethodService()->getAllMethods();
            $currentSpecialMethods = $this->getSpecialServices($currentMethods);

            $this->reportProgress(20);
            $this->syncServices($currentMethods, $apiServices);
            $this->syncServicesSpecial($currentSpecialMethods, $apiSpecialServices);
        }

        $this->reportProgress(100);
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
    protected function getRemoteServices()
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
     */
    protected function syncServices(array $currentMethods, array $apiServices)
    {
        $progress = 20;
        $progressStep = count($currentMethods) > 0 ? (40 / count($currentMethods)) : 40;

        foreach ($currentMethods as $shippingMethod) {
            $this->updateShippingMethod($shippingMethod, $apiServices);

            $progress += $progressStep;
            $this->reportProgress($progress);
        }
        
        $this->reportProgress(60);
        $batch = 0;
        foreach ($apiServices as $service) {
            $batch++;
            if ($batch === 20) {
                $this->reportAlive();
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
     */
    protected function syncServicesSpecial(array $currentMethods, array $apiServices)
    {
        $progress = 60;
        $progressStep = count($currentMethods) > 0 ? (20 / count($currentMethods)) : 20;

        foreach ($currentMethods as $shippingMethod) {
            $this->updateShippingMethod($shippingMethod, $apiServices, true);

            $progress += $progressStep;
            $this->reportProgress($progress);
        }

        $this->reportProgress(80);
        $batch = 0;
        foreach ($apiServices as $service) {
            $batch++;
            if ($batch === 20) {
                $this->reportAlive();
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
    protected function getSpecialServices(array &$apiServices)
    {
        $specialServices = array();

        foreach ($apiServices as $key => $service) {
            if ($this->hasSpecialTag($service)) {
                $specialServices[] = $service;
                unset($apiServices[$key]);
            }
        }

        return $specialServices;
    }

    /**
     * Checks if a service has special_service_tag
     *
     * @param ShippingServiceDetails $service
     *
     * @return bool
     */
    protected function hasSpecialTag($service)
    {
        $tags = isset($service->tags) ? $service->tags : array();

        $tagIds = array_values(Php55::arrayColumn($tags, 'id'));

        return in_array(self::SPECIAL_SERVICE_TAG, $tagIds, true);
    }

    /**
     * Checks if task should be executed.
     *
     * @return bool TRUE if task should execute; otherwise, FALSE.
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoValidationException
     */
    protected function shouldExecute()
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
    protected function getShippingMethodService()
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
    protected function getProxy()
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
    protected function serviceBelongsToMethod(ShippingServiceDetails $service, ShippingMethod $shippingMethod, $special = false)
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
    protected function getCountryService()
    {
        if ($this->countryService === null) {
            $this->countryService = ServiceRegister::getService(WarehouseCountryService::CLASS_NAME);
        }

        return $this->countryService;
    }
}
