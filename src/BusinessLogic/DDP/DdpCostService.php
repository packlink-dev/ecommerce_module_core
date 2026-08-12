<?php

namespace Packlink\BusinessLogic\DDP;

use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Customs\CustomsService;
use Packlink\BusinessLogic\DDP\Interfaces\DdpCostServiceInterface;
use Packlink\BusinessLogic\DDP\Models\DdpCostResponse;
use Packlink\BusinessLogic\Http\DTO\DDP\DdpProductsDetail;
use Packlink\BusinessLogic\Http\DTO\DDP\ShipmentProductsRequest;
use Packlink\BusinessLogic\Http\DTO\DDP\ShipmentProductsRequestItem;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;

/**
 * Class DdpCostService. Wraps the two-call checkout DDP flow: POST /v2/customs-invoices, then a
 * SINGLE-entry POST /pro/shipments/products (never batched -- see ShipmentProductsRequest).
 *
 * DDP cost is a function of the goods and the route, not the carrier service, so one call answers for
 * every DDP-capable service on a route. No caching; the adjustment is exposed but never applied here.
 *
 * @package Packlink\BusinessLogic\DDP
 */
class DdpCostService implements DdpCostServiceInterface
{
    /**
     * @var Proxy
     */
    private $proxy;
    /**
     * @var CustomsService
     */
    private $customsService;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @inheritDoc
     */
    public function getDdpCosts(Order $order, $serviceId)
    {
        if (empty($serviceId)) {
            return null;
        }

        $step = 'assembling the customs invoice';
        try {
            $invoice = $this->getCustomsService()->createCustomsInvoice($order);
            if ($invoice === null) {
                Logger::logWarning(
                    'DDP costs unavailable at checkout: the customs invoice could not be assembled.'
                    . ' Check that the customs data mapping is configured and the order has the data it needs.'
                );

                return null;
            }

            $step = 'creating the checkout customs invoice';
            $invoiceId = $this->getProxy()->createCheckoutCustomsInvoice($invoice);
            if ($invoiceId === null) {
                Logger::logWarning('DDP costs unavailable at checkout: Packlink returned no customs invoice id.');

                return null;
            }

            $step = 'retrieving DDP products';
            $detail = $this->getProxy()->getShipmentProducts($this->buildRequest($order, $serviceId, $invoiceId));
            if ($detail === null || ($detail->ddpFee === null && $detail->customsAndDuties === null)) {
                // A service with no DDP on this route omits the products entirely. Ordinary, not an error.
                return null;
            }

            return $this->buildResponse($serviceId, $detail);
        } catch (\Throwable $e) {
            $this->logFailure($step, $e);

            return null;
        }
    }

    /**
     * Logs a DDP retrieval failure with enough context for the merchant to act on it.
     *
     * Checkout must stay silent for the shopper, so everything still collapses to "no DDP offered" --
     * but the three cases have very different fixes and must not read alike in the log. Packlink's
     * error payload carries a machine-readable `error_code` that validateResponse() discards, so the
     * classification below works off the HTTP status and the message text it does preserve.
     *
     * @param string $step Stage of the flow that failed.
     * @param \Throwable $e Underlying failure.
     *
     * @return void
     */
    private function logFailure($step, \Throwable $e)
    {
        $message = $e->getMessage();
        $status = (int)$e->getCode();

        if (stripos($message, 'hs code') !== false || stripos($message, 'tariff') !== false) {
            // Packlink validates tariff numbers against a current HS revision, so a well-formed but
            // withdrawn code (8517.12, retired in HS 2022) passes our 6-8 digit check and fails here.
            $hint = ' At least one product\'s customs tariff number (HS code) was rejected. It must be a'
                . ' currently valid code, not merely 6-8 digits. This will recur on every checkout with'
                . ' this product until the code is corrected.';
        } elseif ($status >= 400 && $status < 500) {
            $hint = ' Packlink rejected the request data. DDP stays unavailable until the customs'
                . ' configuration or the order data is corrected.';
        } else {
            $hint = ' This looks transient. DDP will be offered again once the API recovers.';
        }

        Logger::logWarning('DDP costs unavailable at checkout while ' . $step . ': ' . $message . $hint);
    }

    /**
     * Builds the single-entry products request. Never more than one entry -- see
     * ShipmentProductsRequest for why batching is unsafe.
     *
     * @param Order $order Checkout order.
     * @param string|int $serviceId Packlink service id.
     * @param string $invoiceId Checkout customs invoice id.
     *
     * @return ShipmentProductsRequest
     */
    private function buildRequest(Order $order, $serviceId, $invoiceId)
    {
        $warehouse = $this->getConfiguration()->getDefaultWarehouse();
        $address = $order->getShippingAddress();

        $item = new ShipmentProductsRequestItem();
        $item->serviceId = $serviceId;
        $item->contentValue = $order->getTotalPrice();
        $item->customsInvoiceId = $invoiceId;
        $item->fromCountry = $warehouse->country;
        $item->fromZip = $warehouse->postalCode;
        $item->fromCity = $warehouse->city;
        $item->toCountry = $address->getCountry();
        $item->toZip = $address->getZipCode();
        $item->toCity = $address->getCity();
        $item->packages = $this->buildPackages($order);

        $request = new ShipmentProductsRequest();
        $request->item = $item;

        return $request;
    }

    /**
     * Collapses the order's items into the parcels the endpoint prices, using the same transformer
     * the draft path uses so checkout and purchase agree on parcel shape.
     *
     * @param Order $order Checkout order.
     *
     * @return Package[]
     */
    private function buildPackages(Order $order)
    {
        $packages = array();
        foreach ($order->getItems() as $orderItem) {
            $quantity = $orderItem->getQuantity() ? $orderItem->getQuantity() : 1;
            for ($i = 0; $i < $quantity; $i++) {
                $packages[] = new Package(
                    $orderItem->getWeight(),
                    $orderItem->getWidth(),
                    $orderItem->getHeight(),
                    $orderItem->getLength()
                );
            }
        }

        /** @var PackageTransformer $transformer */
        $transformer = ServiceRegister::getService(PackageTransformer::CLASS_NAME);

        // transform() falls back to the configured default parcel when the cart yields no packages.
        return array($transformer->transform($packages));
    }

    /**
     * Wraps the response detail together with the owning method's DDP configuration.
     *
     * Note the adjustment fields describe the method that owns $serviceId only. Because DDP cost does
     * not vary by service, the platform makes one call and applies the amount to every DDP carrier --
     * and must read each carrier's own adjustment from its ShippingMethod rather than reusing these.
     *
     * @param string|int $serviceId Requested service id.
     * @param DdpProductsDetail $detail Response detail.
     *
     * @return DdpCostResponse
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function buildResponse($serviceId, DdpProductsDetail $detail)
    {
        $response = new DdpCostResponse();
        $response->serviceId = $serviceId;
        $response->ddpFee = $detail->ddpFee;
        $response->customsAndDuties = $detail->customsAndDuties;

        $methodsByServiceId = $this->getMethodsByServiceId();
        $key = (string)$serviceId;
        if (isset($methodsByServiceId[$key])) {
            $method = $methodsByServiceId[$key];
            $response->effectiveBehavior = $method->getEffectiveDdpBehavior();
            $response->ddpAdjustmentType = $method->getDdpAdjustmentType();
            $response->ddpAdjustmentAmount = $method->getDdpAdjustmentAmount();
        } else {
            $response->effectiveBehavior = DdpBehavior::NONE;
        }

        return $response;
    }

    /**
     * Loads all shipping methods once and indexes them by owned service id.
     *
     * @return ShippingMethod[] Methods keyed by service id.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function getMethodsByServiceId()
    {
        $map = array();
        /** @var ShippingMethod[] $methods */
        $methods = RepositoryRegistry::getRepository(ShippingMethod::CLASS_NAME)->select();

        foreach ($methods as $method) {
            foreach ($method->getShippingServices() as $service) {
                $map[(string)$service->serviceId] = $method;
            }
        }

        return $map;
    }

    /**
     * @return CustomsService
     */
    private function getCustomsService()
    {
        if ($this->customsService === null) {
            $this->customsService = ServiceRegister::getService(CustomsService::CLASS_NAME);
        }

        return $this->customsService;
    }

    /**
     * @return Configuration
     */
    private function getConfiguration()
    {
        if ($this->configuration === null) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }

    /**
     * @return Proxy
     */
    private function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(\Packlink\BusinessLogic\Http\Interfaces\Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}
