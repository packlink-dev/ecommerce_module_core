<?php

namespace Packlink\BusinessLogic\Tasks;

use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Infrastructure\TaskExecution\Task;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
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
     *  Key is service Id, value is an array with keys 'service' and 'serviceDetails'.
     *  'service' holds @see \Packlink\BusinessLogic\Http\DTO\ShippingService object.
     *  'serviceDetails' holds @see \Packlink\BusinessLogic\Http\DTO\ShippingServiceDeliveryDetails object.
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
        $parcel = $config->getDefaultParcel() ?: ParcelInfo::defaultParcel();
        $package = Package::fromArray($parcel->toArray());

        $allServices = array();
        foreach (array_keys(static::$countryParams) as $country) {
            $this->setServices($allServices, $this->getServiceSearchParams($user->country, $country, $package));
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
            $allServices[$deliveryDetail->id] = array(
                'service' => $proxy->getShippingServiceDetails($deliveryDetail->id),
                'serviceDetails' => $deliveryDetail,
            );
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

            unset($apiServices[$shippingMethod->getServiceId()]);

            $progress += $progressStep;
            $this->reportProgress($progress);
        }

        $this->reportProgress(80);
        foreach ($apiServices as $details) {
            $this->getShippingMethodService()->add($details['service'], $details['serviceDetails']);
        }
    }

    /**
     * Updates shipping method from data from Packlink API or deletes it if service does not exist.
     *
     * @param ShippingMethod $shippingMethod Local shipping method.
     * @param array $apiServices Shipping services returned from API.
     */
    protected function updateShippingMethod(ShippingMethod $shippingMethod, array &$apiServices)
    {
        $serviceId = $shippingMethod->getServiceId();
        if (isset($apiServices[$serviceId])) {
            $this->getShippingMethodService()->update(
                $apiServices[$serviceId]['service'],
                $apiServices[$serviceId]['serviceDetails']
            );
        } else {
            $this->getShippingMethodService()->delete($shippingMethod);
        }
    }

    /**
     * Gets search parameters for shipping method discovery.
     *
     * @param string $fromCountry Country code for departure country.
     * @param string $toCountry Country code for destination country.
     * @param Package $package Parcel information.
     *
     * @return ShippingServiceSearch Search parameters object.
     */
    protected function getServiceSearchParams($fromCountry, $toCountry, Package $package)
    {
        $params = new ShippingServiceSearch();

        $params->fromCountry = $fromCountry;
        $params->fromZip = self::$countryParams[$fromCountry];
        $params->toCountry = $toCountry;
        $params->toZip = self::$countryParams[$toCountry];
        $params->packages = array($package);

        return $params;
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

        return $config->getUserInfo() !== null && $config->getDefaultParcel() !== null;
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
}
