<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingService;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;

/**
 * Task to update available shipping services and their default costs.
 *
 * @package Packlink\BusinessLogic\Tasks
 */
class UpdateShippingServicesTask extends Task
{
    /**
     * Mapping between country and main zip code of country's capital city.
     *
     * @var array
     */
    protected static $countryParams = array(
        // Rome
        'IT' => '00118',
        // Madrid
        'ES' => '28001',
        // Berlin
        'DE' => '10115',
        // Paris
        'FR' => '75001',
        // New York
        'US' => '10001',
    );

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
     * Gets all local methods and remote services and synchronizes data.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function execute()
    {
        $this->reportProgress(1);

        if ($this->shouldExecute()) {
            $apiServices = $this->getRemoteServices();
            $currentMethods = $this->getShippingMethodService()->getAllMethods();

            $this->reportProgress(20);
            $this->syncServices($currentMethods, $apiServices);
        }

        $this->reportProgress(100);
    }

    /**
     * Gets all available services for current user.
     *
     * @return array
     *  Key is service Id and value is @see \Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails object.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function getRemoteServices()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        /** @var \Packlink\BusinessLogic\Http\DTO\User $user */
        $user = $config->getUserInfo();
        if (!array_key_exists($user->country, static::$countryParams)) {
            throw new \InvalidArgumentException('User country is not supported: ' . $user->country);
        }

        $parcel = $config->getDefaultParcel() ?: ParcelInfo::defaultParcel();
        $package = Package::fromArray($parcel->toArray());

        $allServices = array();
        foreach (static::$countryParams as $country => $zip) {
            $this->setServices(
                $allServices,
                new ShippingServiceSearch(
                    null,
                    $user->country,
                    static::$countryParams[$user->country],
                    $country,
                    $zip,
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
        $progressStep = count($currentMethods) > 0 ? (60 / count($currentMethods)) : 60;

        foreach ($currentMethods as $shippingMethod) {
            $this->updateShippingMethod($shippingMethod, $apiServices);

            $progress += $progressStep;
            $this->reportProgress($progress);
        }

        $this->reportProgress(80);
        foreach ($apiServices as $service) {
            $this->getShippingMethodService()->add($service);
        }
    }

    /**
     * Updates shipping method from data from Packlink API or deletes it if service does not exist.
     *
     * @param ShippingMethod $shippingMethod Local shipping method.
     * @param ShippingServiceDetails[] $apiServices Shipping services returned from API.
     */
    protected function updateShippingMethod(ShippingMethod $shippingMethod, array &$apiServices)
    {
        $shippingServices = array();
        foreach ($apiServices as $service) {
            if ($this->serviceBelongsToMethod($service, $shippingMethod)) {
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
     * Checks if task should execute. Gets user info from configuration and returns TRUE if user exists.
     * Otherwise, tasks should not execute.
     *
     * @return bool TRUE if task should execute; otherwise, FALSE.
     */
    protected function shouldExecute()
    {
        /** @var Configuration $config */
        $config = ServiceRegister::getService(Configuration::CLASS_NAME);

        return $config->getUserInfo() !== null;
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
    protected function serviceBelongsToMethod(ShippingServiceDetails $service, ShippingMethod $shippingMethod)
    {
        return $service->carrierName === $shippingMethod->getCarrierName()
            && $service->national === $shippingMethod->isNational()
            && $service->expressDelivery === $shippingMethod->isExpressDelivery()
            && $service->departureDropOff === $shippingMethod->isDepartureDropOff()
            && $service->destinationDropOff === $shippingMethod->isDestinationDropOff();
    }
}
