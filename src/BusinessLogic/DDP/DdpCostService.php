<?php

namespace Packlink\BusinessLogic\DDP;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Customs\CustomsService;
use Packlink\BusinessLogic\DDP\Interfaces\DdpCostServiceInterface;
use Packlink\BusinessLogic\DDP\Models\DdpCostResponse;
use Packlink\BusinessLogic\Http\DTO\DDP\DdpProductsDetail;
use Packlink\BusinessLogic\Http\DTO\DDP\ShipmentProductsRequest;
use Packlink\BusinessLogic\Http\DTO\DDP\ShipmentProductsRequestItem;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\ShippingMethod\Models\ShippingMethod;

/**
 * Class DdpCostService. Wraps the two-call checkout DDP flow
 * (POST /v2/customs-invoices then one batched POST /pro/shipments/products).
 * No caching; the adjustment is exposed but never applied here.
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
     * @inheritDoc
     */
    public function getDdpCosts(Order $order, array $serviceIds)
    {
        if (empty($serviceIds)) {
            return array();
        }

        try {
            if ($this->getConfigService()->getCustomsMappings() === null) {
                return array();
            }

            $invoice = $this->getCustomsService()->createCustomsInvoice($order);
            if ($invoice === null) {
                return array();
            }

            $invoiceId = $this->getProxy()->createCheckoutCustomsInvoice($invoice);
            if ($invoiceId === null) {
                return array();
            }

            $details = $this->getProxy()->getShipmentProducts($this->buildRequest($order, $serviceIds, $invoiceId));

            return $this->mapCosts($serviceIds, $details);
        } catch (\Exception $e) {
            Logger::logWarning('Failed to retrieve DDP costs at checkout: ' . $e->getMessage());

            return array();
        }
    }

    /**
     * @inheritDoc
     */
    public function resolveEffectiveBehavior(ShippingMethod $method)
    {
        $level = $method->getDdpSupportLevel();

        if ($level === DdpBehavior::LEVEL_MANDATORY) {
            return DdpBehavior::MANDATORY;
        }

        if ($level === DdpBehavior::LEVEL_SUPPORTED) {
            $behavior = $method->getDdpBehavior();
            if ($behavior === DdpBehavior::ENFORCED || $behavior === DdpBehavior::OPTIONAL) {
                return $behavior;
            }
        }

        return DdpBehavior::NONE;
    }

    /**
     * Builds one batched products request for all requested services.
     *
     * @param Order $order Checkout order.
     * @param string[]|int[] $serviceIds Packlink service ids.
     * @param string $invoiceId Checkout customs invoice id.
     *
     * @return ShipmentProductsRequest
     */
    private function buildRequest(Order $order, array $serviceIds, $invoiceId)
    {
        $request = new ShipmentProductsRequest();

        foreach ($serviceIds as $serviceId) {
            $item = new ShipmentProductsRequestItem();
            $item->serviceId = $serviceId;
            $item->contentValue = $order->getTotalPrice();
            $item->contentValueCurrency = $order->getCurrency();
            $item->customsInvoiceId = $invoiceId;

            $request->items[] = $item;
        }

        return $request;
    }

    /**
     * Maps response details to per-service cost responses, attaching the owning
     * method's effective behavior and adjustment configuration.
     *
     * @param string[]|int[] $serviceIds Requested service ids, in request order.
     * @param DdpProductsDetail[] $details Response details.
     *
     * @return DdpCostResponse[] Costs keyed by service id.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    private function mapCosts(array $serviceIds, array $details)
    {
        $methodsByServiceId = $this->getMethodsByServiceId();
        $result = array();

        foreach (array_values($serviceIds) as $index => $serviceId) {
            $detail = $this->matchDetail($details, $serviceId, $index);
            if ($detail === null) {
                continue;
            }

            $response = new DdpCostResponse();
            $response->serviceId = $serviceId;
            $response->ddpFee = $detail->ddpFee;
            $response->customsAndDuties = $detail->customsAndDuties;

            $key = (string)$serviceId;
            if (isset($methodsByServiceId[$key])) {
                $method = $methodsByServiceId[$key];
                $response->effectiveBehavior = $this->resolveEffectiveBehavior($method);
                $response->ddpAdjustmentType = $method->getDdpAdjustmentType();
                $response->ddpAdjustmentAmount = $method->getDdpAdjustmentAmount();
            } else {
                $response->effectiveBehavior = DdpBehavior::NONE;
                $response->ddpAdjustmentType = null;
                $response->ddpAdjustmentAmount = 0.0;
            }

            $result[$serviceId] = $response;
        }

        return $result;
    }

    /**
     * Matches a response detail to a requested service: by service id when the response
     * carries one, otherwise by position (matching rule to confirm against the Packlink
     * API reference).
     *
     * @param DdpProductsDetail[] $details Response details.
     * @param string|int $serviceId Requested service id.
     * @param int $index Position of the service in the request.
     *
     * @return DdpProductsDetail|null
     */
    private function matchDetail(array $details, $serviceId, $index)
    {
        foreach ($details as $detail) {
            if ($detail->serviceId !== null && (string)$detail->serviceId === (string)$serviceId) {
                return $detail;
            }
        }

        $indexed = array_values($details);
        if (isset($indexed[$index]) && $indexed[$index]->serviceId === null) {
            return $indexed[$index];
        }

        return null;
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
     * @return \Packlink\BusinessLogic\Configuration
     */
    private function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
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
