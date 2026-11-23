<?php

namespace Packlink\BusinessLogic\Controllers;

use Logeecom\Infrastructure\Exceptions\ServiceNotRegisteredException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\PlanPrice;
use Packlink\BusinessLogic\SimulatedPrices\DTO\SimulatedPricesSearch;
use Packlink\BusinessLogic\SimulatedPrices\Interfaces\ShopOriginProvider;
use Packlink\BusinessLogic\SimulatedPrices\SimulatedPricesService;
use Packlink\BusinessLogic\Subscription\SubscriptionService;
use Packlink\BusinessLogic\Subscription\UpgradeUrlProvider;

/**
 * Class SimulatedPricesController. Entry point of the "Discover our shipping rates" panel for every module.
 * A module only adds what is its own: the route, a throttle, and optionally a ShopOriginProvider.
 *
 * @package Packlink\BusinessLogic\Controllers
 */
class SimulatedPricesController
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Payload keys a browser may send. The origin is never one of them: it is the warehouse of a connected
     * merchant, or the shop address from ShopOriginProvider.
     */
    private static $browserKeys = array('market', 'toCountry', 'toPostalCode');
    /**
     * Error codes the merchant can act on: the panel shows a message, not a failure.
     */
    private static $unprocessableErrors = array(
        SimulatedPricesService::ERROR_VALIDATION,
        SimulatedPricesService::ERROR_PREMIUM,
        SimulatedPricesService::ERROR_MARKET_UNAVAILABLE,
    );
    /**
     * @var SimulatedPricesService
     */
    private $service;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * SimulatedPricesController constructor.
     */
    public function __construct()
    {
        $this->service = ServiceRegister::getService(SimulatedPricesService::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Returns the static part of the panel, without any Packlink call, for a module that renders the panel
     * itself and already knows the connection state and the plan.
     *
     * @return array Keys: enabled, defaultMarket, markets, parcel, accountMarket (the only chip of a connected
     *   merchant; null without an account).
     */
    public function getConfiguration()
    {
        return array(
            'enabled' => $this->service->isAvailable(),
            'accountMarket' => $this->service->getAccountMarket(),
            'defaultMarket' => SimulatedPricesService::DEFAULT_MARKET,
            'markets' => SimulatedPricesService::getMarkets(),
            'parcel' => SimulatedPricesService::getParcel(),
        );
    }

    /**
     * Returns what the panel needs before the first quote. A connected merchant costs one subscription call;
     * a merchant without an account costs none.
     *
     * @return array Keys: connected, enabled, currentTier, defaultMarket, markets, parcel, accountMarket, upgradeUrl.
     */
    public function getData()
    {
        $data = $this->getConfiguration();
        $connected = (bool)$this->configuration->getAuthorizationToken();
        $currentTier = null;
        $upgradeUrl = null;
        if ($connected && $data['enabled']) {
            /** @var SubscriptionService $subscriptionService */
            $subscriptionService = ServiceRegister::getService(SubscriptionService::CLASS_NAME);
            $currentTier = $subscriptionService->getPlanTier() ?: PlanPrice::TIER_FREE;
            $userInfo = $this->configuration->getUserInfo();
            $upgradeUrl = UpgradeUrlProvider::getUpgradeUrl(
                $userInfo !== null ? strtoupper((string)$userInfo->country) : ''
            );
        }

        return array(
            'connected' => $connected,
            'enabled' => $data['enabled'] && $currentTier !== PlanPrice::TIER_PREMIUM,
            'currentTier' => $currentTier,
            'defaultMarket' => $data['defaultMarket'],
            'markets' => $data['markets'],
            'parcel' => $data['parcel'],
            'accountMarket' => $data['accountMarket'],
            'upgradeUrl' => $upgradeUrl,
        );
    }

    /**
     * Quotes the sample parcel. Only market, toCountry and toPostalCode are read from the payload; for a
     * merchant without an account the origin comes from ShopOriginProvider, else the capital of the market.
     *
     * @param array $payload Request body as sent by the browser.
     *
     * @return array See SimulatedPricesService::search().
     */
    public function search(array $payload)
    {
        $input = array_intersect_key($payload, array_flip(self::$browserKeys));
        if (!$this->configuration->getAuthorizationToken()) {
            $input = array_merge($input, $this->getShopOrigin());
        }

        return $this->service->search(SimulatedPricesSearch::fromArray($input));
    }

    /**
     * Returns the HTTP status for an error code of SimulatedPricesService::search(). "No services for this
     * route" is a normal answer, not a failure.
     *
     * @param string|null $error
     *
     * @return int
     */
    public static function getHttpStatus($error)
    {
        if ($error === null || $error === SimulatedPricesService::ERROR_NO_SERVICES) {
            return 200;
        }

        if ($error === SimulatedPricesService::ERROR_NOT_ENABLED) {
            return 403;
        }

        return in_array($error, self::$unprocessableErrors, true) ? 422 : 502;
    }

    /**
     * Shop address of a merchant without an account. A missing or failing provider is not an error: the
     * service falls back to the capital city of the market.
     *
     * @return array Keys fromCountry, fromPostalCode, or empty.
     */
    private function getShopOrigin()
    {
        try {
            /** @var ShopOriginProvider $provider */
            $provider = ServiceRegister::getService(ShopOriginProvider::CLASS_NAME);
            $origin = $provider->getOrigin();
        } catch (ServiceNotRegisteredException $e) {
            return array();
        } catch (\Exception $e) {
            Logger::logWarning(
                'Simulated prices: shop address unavailable, using the capital city of the market.',
                'Core',
                array('error' => $e->getMessage())
            );

            return array();
        }

        if (!is_array($origin) || empty($origin['country']) || empty($origin['postalCode'])) {
            return array();
        }

        return array('fromCountry' => $origin['country'], 'fromPostalCode' => $origin['postalCode']);
    }
}
