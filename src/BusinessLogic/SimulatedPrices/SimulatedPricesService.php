<?php

namespace Packlink\BusinessLogic\SimulatedPrices;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\PlanPrice;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Exceptions\PacklinkErrorException;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\SimulatedPrices\DTO\SimulatedPricesSearch;
use Packlink\BusinessLogic\Subscription\SubscriptionService;

/**
 * Class SimulatedPricesService. Quotes a fixed sample parcel on every Packlink PRO plan, to promote higher
 * plans to connected merchants and to preview prices to merchants without a Packlink account.
 * Connected merchants are quoted through the authenticated services endpoint; merchants without an account
 * through the unregistered-user pricing endpoint, without credentials. The result never throws: failures
 * come back as an error code.
 *
 * @package Packlink\BusinessLogic\SimulatedPrices
 */
class SimulatedPricesService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Simulated prices are offered on the Packlink PRO platform only.
     */
    const ERROR_NOT_ENABLED = 'not_enabled';
    /**
     * Invalid input, or Packlink rejected the shipment details.
     */
    const ERROR_VALIDATION = 'validation';
    /**
     * The merchant is on the highest plan; there is nothing to promote.
     */
    const ERROR_PREMIUM = 'premium';
    /**
     * Packlink offers no price preview for the selected market.
     */
    const ERROR_MARKET_UNAVAILABLE = 'market_unavailable';
    /**
     * Packlink failed or could not be reached.
     */
    const ERROR_PACKLINK = 'packlink_error';
    /**
     * The search succeeded but no service has a displayable price.
     */
    const ERROR_NO_SERVICES = 'no_services';
    /**
     * The only platform whose plans the panel quotes.
     */
    const PLATFORM_PRO = 'PRO';
    /**
     * Market selected when the panel is first shown.
     */
    const DEFAULT_MARKET = 'ES';
    /**
     * Fixed sample parcel: length in cm.
     */
    const PARCEL_LENGTH = 15;
    /**
     * Fixed sample parcel: width in cm.
     */
    const PARCEL_WIDTH = 10;
    /**
     * Fixed sample parcel: height in cm.
     */
    const PARCEL_HEIGHT = 3;
    /**
     * Fixed sample parcel: weight in kg. A string, so the decimal separator is a dot under every locale.
     */
    const PARCEL_WEIGHT = '0.5';
    /**
     * Singleton instance of this service.
     *
     * @var static
     */
    protected static $instance;

    /**
     * Returns the market chips in display order with the capital city used as the default address.
     *
     * @return array[] Each item: code, postalCode, city.
     */
    public static function getMarkets()
    {
        return array(
            array('code' => 'ES', 'postalCode' => '28001', 'city' => 'Madrid'),
            array('code' => 'FR', 'postalCode' => '75001', 'city' => 'Paris'),
            array('code' => 'DE', 'postalCode' => '10115', 'city' => 'Berlin'),
            array('code' => 'IT', 'postalCode' => '00118', 'city' => 'Roma'),
            array('code' => 'GB', 'postalCode' => 'SW1A 1AA', 'city' => 'London'),
        );
    }

    /**
     * Returns the fixed sample parcel.
     *
     * @return array Keys: length, width, height (cm) and weight (kg).
     */
    public static function getParcel()
    {
        return array(
            'length' => self::PARCEL_LENGTH,
            'width' => self::PARCEL_WIDTH,
            'height' => self::PARCEL_HEIGHT,
            'weight' => (float)self::PARCEL_WEIGHT,
        );
    }

    /**
     * Whether the panel is offered at all: the active brand is the Packlink PRO platform. The plan check of a
     * connected merchant (PREMIUM sees no panel) is separate, because it costs a Packlink call.
     *
     * @return bool
     */
    public function isAvailable()
    {
        return $this->getBrandConfigurationService()->get()->platformCode === self::PLATFORM_PRO;
    }

    /**
     * Quotes the sample parcel for the given search.
     *
     * @param SimulatedPricesSearch $search
     *
     * @return array Keys: connected (bool), market (string), currentTier (string|null), tiers (string[]),
     *   services (array[]), fromPrice (array|null), error (string|null).
     */
    public function search(SimulatedPricesSearch $search)
    {
        $connected = (bool)$this->getConfiguration()->getAuthorizationToken();
        $result = array(
            'connected' => $connected,
            'market' => $search->market,
            'currentTier' => null,
            'tiers' => array(),
            'services' => array(),
            'fromPrice' => null,
            'error' => null,
        );

        if (!$this->isAvailable()) {
            $result['error'] = self::ERROR_NOT_ENABLED;

            return $result;
        }

        // A connected merchant sees its own account's prices, and an account quotes only shipments from its
        // own country: its market is fixed, whatever chip the browser sends.
        $accountMarket = $connected ? $this->getAccountMarket() : null;
        $market = $this->findMarket($accountMarket !== null ? $accountMarket : $search->market);
        if ($market !== null) {
            $result['market'] = $market['code'];
        }

        $destination = $market !== null ? $this->resolveDestination($search, $market) : null;
        if ($destination === null) {
            $result['error'] = self::ERROR_VALIDATION;

            return $result;
        }

        if (!$connected) {
            return $this->searchWithoutAccount($search, $market, $destination, $result);
        }

        $tier = $this->getSubscriptionService()->getPlanTier();
        if ($tier === null) {
            $tier = PlanPrice::TIER_FREE;
        }

        if ($tier === PlanPrice::TIER_PREMIUM) {
            $result['currentTier'] = $tier;
            $result['error'] = self::ERROR_PREMIUM;

            return $result;
        }

        return $this->searchConnected($market, $destination, $tier, $result);
    }

    /**
     * Market of a connected merchant's Packlink account: the account country when it is one of the market
     * chips; null without an account, or when the country is unknown or not a market. Makes no Packlink call.
     *
     * @return string|null
     */
    public function getAccountMarket()
    {
        if (!$this->getConfiguration()->getAuthorizationToken()) {
            return null;
        }

        $country = $this->getAccountCountry();

        return $country !== '' && $this->findMarket($country) !== null ? $country : null;
    }

    /**
     * Quotes a connected merchant on the market of its account: own account prices, current and higher
     * tiers. The origin is the default warehouse when it lies in that market, otherwise the market's
     * capital city, because the account has no services from another country.
     *
     * @param array $market Market chip.
     * @param array $destination Keys: country, postalCode.
     * @param string $tier Current plan tier.
     * @param array $result Result skeleton.
     *
     * @return array
     */
    protected function searchConnected(array $market, array $destination, $tier, array $result)
    {
        $result['currentTier'] = $tier;

        $fromCountry = $market['code'];
        $fromPostalCode = $market['postalCode'];
        $warehouse = $this->getConfiguration()->getDefaultWarehouse();
        if ($warehouse !== null && !empty($warehouse->postalCode)
            && strtoupper((string)$warehouse->country) === $market['code']
        ) {
            $fromCountry = $market['code'];
            $fromPostalCode = $warehouse->postalCode;
        }

        $search = $this->createServiceSearch(
            $fromCountry,
            $fromPostalCode,
            $destination['country'],
            $destination['postalCode']
        );
        $search->priceSimulation = ShippingServiceSearch::PRICE_SIMULATION_ALL;

        try {
            $services = $this->getProxy()->getShippingServicesDeliveryDetails($search);
        } catch (HttpBaseException $e) {
            Logger::logWarning('Simulated prices search failed: ' . $e->getMessage(), 'Core');
            $result['error'] = $e->getCode() === 400 ? self::ERROR_VALIDATION : self::ERROR_PACKLINK;

            return $result;
        }

        return $this->buildResult($services, $this->getTiersFrom($tier), $tier, $result);
    }

    /**
     * Quotes a merchant without a Packlink account: the chip selects the market and the origin (the shop
     * address when it lies in that market, else its capital), every tier is shown and no tier is current.
     *
     * @param SimulatedPricesSearch $search
     * @param array $market Market chip.
     * @param array $destination Keys: country, postalCode.
     * @param array $result Result skeleton.
     *
     * @return array
     */
    protected function searchWithoutAccount(SimulatedPricesSearch $search, array $market, array $destination, array $result)
    {
        $fromCountry = $market['code'];
        $fromPostalCode = $market['postalCode'];
        if ($search->fromCountry !== '' && $search->fromPostalCode !== '') {
            if (!$this->isCountryCode($search->fromCountry)) {
                $result['error'] = self::ERROR_VALIDATION;

                return $result;
            }

            // The chip is the platform country: the shop address is used only when it lies in that market,
            // otherwise the market's capital, so every chip shows that market's domestic services.
            if ($search->fromCountry === $market['code']) {
                $fromPostalCode = $search->fromPostalCode;
            }
        }

        $serviceSearch = $this->createServiceSearch(
            $fromCountry,
            $fromPostalCode,
            $destination['country'],
            $destination['postalCode']
        );

        try {
            $services = $this->getProxy()->getSimulatedShippingServices(
                $serviceSearch,
                $this->getBrandConfigurationService()->get()->platformCode,
                $market['code']
            );
        } catch (PacklinkErrorException $e) {
            $result['error'] = $this->mapPacklinkError($e);

            return $result;
        } catch (HttpBaseException $e) {
            Logger::logWarning('Simulated prices search failed: ' . $e->getMessage(), 'Core');
            $result['error'] = $e->getCode() === 400 ? self::ERROR_VALIDATION : self::ERROR_PACKLINK;

            return $result;
        }

        return $this->buildResult($services, PlanPrice::getTiers(), null, $result);
    }

    /**
     * Country of the connected Packlink account, upper-cased; empty when it is not known.
     *
     * @return string
     */
    protected function getAccountCountry()
    {
        $user = $this->getConfiguration()->getUserInfo();

        return $user !== null ? strtoupper(trim((string)$user->country)) : '';
    }

    /**
     * Maps an unsuccessful unregistered-user pricing response to an error code. The Packlink error code is
     * read first, because "market not available" comes back as 404 or, for an unmapped market such as GB, as 400.
     *
     * @param PacklinkErrorException $e
     *
     * @return string
     */
    protected function mapPacklinkError(PacklinkErrorException $e)
    {
        $errorCode = $e->getErrorCode();
        if ($e->getCode() === 404 || $errorCode === 'PLATFORM_REQUIRED') {
            return self::ERROR_MARKET_UNAVAILABLE;
        }

        if ($errorCode === 'CLIENT_REQUIRED' || in_array($e->getCode(), array(401, 403), true)) {
            Logger::logError(
                'Unregistered-user pricing rejected credentials although none must be sent.',
                'Core',
                array('status' => $e->getCode(), 'errorCode' => $errorCode)
            );

            return self::ERROR_PACKLINK;
        }

        if ($e->getCode() === 400 && $errorCode === '') {
            return self::ERROR_VALIDATION;
        }

        Logger::logWarning(
            'Unregistered-user pricing failed.',
            'Core',
            array('status' => $e->getCode(), 'errorCode' => $errorCode)
        );

        return self::ERROR_PACKLINK;
    }

    /**
     * Converts Packlink services to the result: visible tiers only, missing prices left out, services without
     * a displayable price dropped, sorted by the first visible column and then the next ones.
     *
     * @param ShippingServiceDetails[] $services
     * @param string[] $tiers Visible tiers, lowest first.
     * @param string|null $currentTier Current tier, or null for a merchant without an account.
     * @param array $result Result skeleton.
     *
     * @return array
     */
    protected function buildResult(array $services, array $tiers, $currentTier, array $result)
    {
        $result['tiers'] = $tiers;
        $rows = array();
        foreach ($services as $service) {
            $prices = $this->getVisiblePrices($service, $tiers, $currentTier);
            if (empty($prices)) {
                continue;
            }

            $rows[] = array(
                'id' => $service->id,
                'carrierName' => $service->carrierName,
                'serviceName' => $service->serviceName,
                'logoUrl' => $this->getShopShippingMethodService()->getCarrierLogoFilePath($service->carrierName),
                'transitTime' => $service->transitTime,
                'prices' => $prices,
            );
        }

        if (empty($rows)) {
            $result['error'] = self::ERROR_NO_SERVICES;

            return $result;
        }

        usort(
            $rows,
            function ($first, $second) use ($tiers) {
                return SimulatedPricesService::compareRows($first, $second, $tiers);
            }
        );

        $result['fromPrice'] = $this->getFromPrice($rows, $this->getNoteTier($tiers, $currentTier));
        foreach ($rows as $row) {
            $row['prices'] = array_values($row['prices']);
            $result['services'][] = $row;
        }

        return $result;
    }

    /**
     * Orders two result rows by the price of each visible tier in turn; a missing price sorts last.
     *
     * @param array $first
     * @param array $second
     * @param string[] $tiers
     *
     * @return int
     */
    public static function compareRows(array $first, array $second, array $tiers)
    {
        foreach ($tiers as $tier) {
            $firstPrice = self::findPrice($first, $tier);
            $secondPrice = self::findPrice($second, $tier);
            if ($firstPrice === $secondPrice) {
                continue;
            }

            if ($firstPrice === null) {
                return 1;
            }

            if ($secondPrice === null) {
                return -1;
            }

            return $firstPrice < $secondPrice ? -1 : 1;
        }

        return 0;
    }

    /**
     * Returns the price of a row on a tier, or null.
     *
     * @param array $row
     * @param string $tier
     *
     * @return float|null
     */
    protected static function findPrice(array $row, $tier)
    {
        return isset($row['prices'][$tier]['totalPrice']) ? (float)$row['prices'][$tier]['totalPrice'] : null;
    }

    /**
     * Returns the displayable prices of a service keyed by tier, in tier order.
     *
     * @param ShippingServiceDetails $service
     * @param string[] $tiers
     * @param string|null $currentTier
     *
     * @return array
     */
    protected function getVisiblePrices(ShippingServiceDetails $service, array $tiers, $currentTier)
    {
        $byTier = array();
        foreach ($service->pricesPerPlan as $plan) {
            $tier = PlanPrice::tierFromPlanCode($plan->planCode);
            if ($tier === null || $plan->totalPrice === null || isset($byTier[$tier])) {
                continue;
            }

            $byTier[$tier] = $plan;
        }

        $prices = array();
        foreach ($tiers as $tier) {
            if (!isset($byTier[$tier])) {
                continue;
            }

            $prices[$tier] = array(
                'tier' => $tier,
                'totalPrice' => (float)$byTier[$tier]->totalPrice,
                'currency' => $byTier[$tier]->currency,
                'isCurrent' => $tier === $currentTier,
            );
        }

        return $prices;
    }

    /**
     * Returns the lowest price on the note tier, or null when no service has one.
     *
     * @param array $rows Result rows with prices keyed by tier.
     * @param string|null $tier
     *
     * @return array|null Keys: tier, totalPrice, currency.
     */
    protected function getFromPrice(array $rows, $tier)
    {
        $lowest = null;
        foreach ($rows as $row) {
            if ($tier === null || !isset($row['prices'][$tier])) {
                continue;
            }

            if ($lowest === null || $row['prices'][$tier]['totalPrice'] < $lowest['totalPrice']) {
                $lowest = array(
                    'tier' => $tier,
                    'totalPrice' => $row['prices'][$tier]['totalPrice'],
                    'currency' => $row['prices'][$tier]['currency'],
                );
            }
        }

        return $lowest;
    }

    /**
     * Returns the tier of the "Start shipping from" note: Free for a merchant without an account, otherwise
     * the next plan above the current one.
     *
     * @param string[] $tiers
     * @param string|null $currentTier
     *
     * @return string|null
     */
    protected function getNoteTier(array $tiers, $currentTier)
    {
        if ($currentTier === null) {
            return PlanPrice::TIER_FREE;
        }

        $index = array_search($currentTier, $tiers, true);

        return $index !== false && isset($tiers[$index + 1]) ? $tiers[$index + 1] : null;
    }

    /**
     * Returns the current tier and every higher tier.
     *
     * @param string $tier
     *
     * @return string[]
     */
    protected function getTiersFrom($tier)
    {
        $tiers = PlanPrice::getTiers();
        $index = array_search($tier, $tiers, true);

        return $index === false ? $tiers : array_slice($tiers, $index);
    }

    /**
     * Resolves the destination: the market capital unless the search names one.
     *
     * @param SimulatedPricesSearch $search
     * @param array $market
     *
     * @return array|null Keys: country, postalCode; null when invalid.
     */
    protected function resolveDestination(SimulatedPricesSearch $search, array $market)
    {
        $country = $search->toCountry !== '' ? $search->toCountry : $market['code'];
        $postalCode = $search->toPostalCode;
        if ($postalCode === '' && $country === $market['code']) {
            $postalCode = $market['postalCode'];
        }

        if (!$this->isCountryCode($country) || $postalCode === '' || strlen($postalCode) > 16) {
            return null;
        }

        return array('country' => $country, 'postalCode' => $postalCode);
    }

    /**
     * @param string $code
     *
     * @return array|null
     */
    protected function findMarket($code)
    {
        foreach (self::getMarkets() as $market) {
            if ($market['code'] === $code) {
                return $market;
            }
        }

        return null;
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    protected function isCountryCode($value)
    {
        return (bool)preg_match('/^[A-Z]{2}$/', $value);
    }

    /**
     * Builds the services search for the fixed sample parcel.
     *
     * @param string $fromCountry
     * @param string $fromPostalCode
     * @param string $toCountry
     * @param string $toPostalCode
     *
     * @return ShippingServiceSearch
     */
    protected function createServiceSearch($fromCountry, $fromPostalCode, $toCountry, $toPostalCode)
    {
        $package = new Package(self::PARCEL_WEIGHT, self::PARCEL_WIDTH, self::PARCEL_HEIGHT, self::PARCEL_LENGTH);

        return new ShippingServiceSearch(
            null,
            $fromCountry,
            $fromPostalCode,
            $toCountry,
            $toPostalCode,
            array($package)
        );
    }

    /**
     * @return Configuration
     */
    protected function getConfiguration()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * @return Proxy
     */
    protected function getProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }

    /**
     * @return SubscriptionService
     */
    protected function getSubscriptionService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(SubscriptionService::CLASS_NAME);
    }

    /**
     * @return ShopShippingMethodService
     */
    protected function getShopShippingMethodService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ShopShippingMethodService::CLASS_NAME);
    }

    /**
     * @return BrandConfigurationService
     */
    protected function getBrandConfigurationService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(BrandConfigurationService::CLASS_NAME);
    }
}
