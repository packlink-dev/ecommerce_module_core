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
     * Per-call budget for POST/PUT /v2/customs-invoices (AC-7.4.1). Measured at 245-600 ms.
     */
    const INVOICE_TIMEOUT_SECONDS = 2;

    /**
     * Per-call budget for POST /pro/shipments/products (AC-7.4.1). Measured at 1250-2050 ms, so this
     * is the call worth bounding.
     */
    const PRODUCTS_TIMEOUT_SECONDS = 4;

    /**
     * Connect budget. Separate from the total so a dead host fails fast instead of burning the whole
     * per-call budget on a TCP handshake.
     */
    const CONNECT_TIMEOUT_SECONDS = 2;

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

        // Answer the configuration question before spending two API calls on it. Without this the
        // same misconfiguration surfaces further down as a null invoice, indistinguishable in the
        // log from an order that legitimately needs no customs.
        $mapping = $this->getConfiguration()->getCustomsMappings();
        if ($mapping === null || !$mapping->isConfigured()) {
            Logger::logWarning(
                'DDP costs unavailable at checkout: the customs configuration is incomplete.'
                . ' Complete the customs settings (reason for export, sender tax id, receiver user type,'
                . ' and a resolvable HS code and country of origin) before offering duties cost.'
            );

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
     * Prices SEVERAL shipments at once, concurrently.
     *
     * Customs value is goods plus freight, so a carrier that ships the same cart for more money owes
     * more duty - measured on a live route, +4.09 of freight moved the duty +0.44. Every carrier
     * therefore needs its own lookup, and one lookup is two sequential Packlink calls (measured 245 ms
     * for the invoice, 1730 ms for the products call). Done one after another that is ~1975 ms per
     * carrier, which passes Shopify's rate window at four carriers and blows it at six.
     *
     * The calls are not a chain, though. The invoices do not depend on each other, and each products
     * call depends only on ITS OWN invoice. So the work is two waves of concurrent requests, and the
     * wall time is the slowest call in each wave rather than the sum: 10 carriers measured 2750 ms
     * against 19750 ms sequential, with every figure identical to the sequential answers.
     *
     * Batching the products endpoint instead was rejected on evidence: it accepts an array but
     * returns no `service_id` and an empty `packlink_reference`, so entries can only be matched by
     * request order, and 10 entries answered HTTP 500. Separate concurrent calls carry their own
     * responses and need no correlation key at all.
     *
     * `curl_multi` is used directly rather than the infrastructure HttpClient, which has no
     * concurrent primitive. That skips the platform's own HTTP stack, which matters on WordPress,
     * where `wp_remote_*` carries the site's proxy and SSL settings - the trade is deliberate and
     * documented rather than hidden.
     *
     * An item may carry an `invoiceId` from a previous quote. The invoice is then PUT rather than
     * POSTed, which replaces its whole content - goods, items and freight - and re-points it at this
     * cart. Checkout invoices (`/v2/customs-invoices`) exist only to obtain a quote and are never
     * attached to a shipment; the draft builds its own on `/customs-invoices` (v1). Reuse is
     * therefore safe, and it matters because Packlink offers no way to delete or even list them.
     *
     * @param array $items Lookups keyed however the caller likes, each an array of:
     *                     - 'order'     Order       the cart, with setShippingCost() set to THIS
     *                                               carrier's freight (that is what makes the
     *                                               lookups differ);
     *                     - 'serviceId' string|int  a service that serves the route;
     *                     - 'invoiceId' string|null an invoice to re-point instead of creating one.
     *
     * @return array Same keys, each an array of:
     *               - 'invoiceId' string|null  the invoice used, to persist for reuse;
     *               - 'costs'     DdpCostResponse|null  null when this route carries no duty;
     *               - 'error'     string|null  why this one failed, when it did.
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    public function getDdpCostsMany(array $items)
    {
        $results = array();

        foreach ($items as $key => $item) {
            $results[$key] = array('invoiceId' => null, 'costs' => null, 'error' => null);
        }

        if (empty($items)) {
            return $results;
        }

        // Asked once for the whole batch, not once per item: it is a property of the shop.
        $mapping = $this->getConfiguration()->getCustomsMappings();
        if ($mapping === null || !$mapping->isConfigured()) {
            Logger::logWarning(
                'DDP costs unavailable at checkout: the customs configuration is incomplete.'
                . ' Complete the customs settings (reason for export, sender tax id, receiver user type,'
                . ' and a resolvable HS code and country of origin) before offering duties cost.'
            );

            foreach ($results as $key => $ignored) {
                $results[$key]['error'] = 'customs configuration incomplete';
            }

            return $results;
        }

        // ---------------------------------------------------------------- wave 1: the invoices
        $invoiceRequests = array();

        foreach ($items as $key => $item) {
            try {
                $invoice = $this->getCustomsService()->createCustomsInvoice($item['order']);
            } catch (\Throwable $e) {
                $results[$key]['error'] = 'invoice assembly failed: ' . $e->getMessage();
                continue;
            }

            if ($invoice === null) {
                $results[$key]['error'] = 'the customs invoice could not be assembled';
                continue;
            }

            $existing = isset($item['invoiceId']) && $item['invoiceId'] !== null && $item['invoiceId'] !== ''
                ? (string)$item['invoiceId']
                : null;

            $invoiceRequests[$key] = array(
                'method' => $existing === null ? 'POST' : 'PUT',
                'url' => Proxy::BASE_URL . 'v2/customs-invoices' . ($existing === null ? '' : '/' . $existing),
                'body' => json_encode($invoice->toArray()),
                'timeout' => self::INVOICE_TIMEOUT_SECONDS,
                'reusing' => $existing,
            );
        }

        if (empty($invoiceRequests)) {
            return $results;
        }

        foreach ($this->requestConcurrently($invoiceRequests) as $key => $response) {
            $reused = $invoiceRequests[$key]['reusing'];

            if ($response['code'] < 200 || $response['code'] >= 300) {
                $results[$key]['error'] = 'customs invoice ' . ($reused === null ? 'creation' : 'update')
                    . ' answered HTTP ' . $response['code'];
                continue;
            }

            $decoded = json_decode($response['body'], true);
            $id = is_array($decoded) && isset($decoded['id']) ? (string)$decoded['id'] : $reused;

            if ($id === null || $id === '') {
                $results[$key]['error'] = 'Packlink returned no customs invoice id';
                continue;
            }

            $results[$key]['invoiceId'] = $id;
        }

        // ---------------------------------------------------------------- wave 2: the products calls
        $productRequests = array();

        foreach ($items as $key => $item) {
            if ($results[$key]['invoiceId'] === null) {
                continue;
            }

            $request = $this->buildRequest($item['order'], $item['serviceId'], $results[$key]['invoiceId']);

            $productRequests[$key] = array(
                'method' => 'POST',
                'url' => Proxy::BASE_URL . 'pro/shipments/products',
                'body' => json_encode($request->toArray()),
                'timeout' => self::PRODUCTS_TIMEOUT_SECONDS,
            );
        }

        if (empty($productRequests)) {
            return $results;
        }

        $methodsByServiceId = $this->getMethodsByServiceId();
        $corrections = array();

        foreach ($this->requestConcurrently($productRequests) as $key => $response) {
            if ($response['code'] < 200 || $response['code'] >= 300) {
                $results[$key]['error'] = 'products call answered HTTP ' . $response['code'];
                continue;
            }

            $decoded = json_decode($response['body'], true);
            $details = is_array($decoded) && isset($decoded['products_details'])
                ? $decoded['products_details']
                : array();

            if (empty($details[0]) || !is_array($details[0])) {
                // A service with no DDP on this route omits the products entirely. Ordinary, not an error.
                continue;
            }

            $detail = DdpProductsDetail::fromArray($details[0]);

            if ($detail->ddpFee === null && $detail->customsAndDuties === null) {
                continue;
            }

            $results[$key]['costs'] = $this->buildResponse(
                $items[$key]['serviceId'],
                $detail,
                $methodsByServiceId
            );

            // Packlink's OWN carrier price for this shipment, which is what it bills the duty on.
            $porterage = $this->readPorterage($details[0]);
            $declared = (float)$items[$key]['order']->getShippingCost();

            if ($porterage !== null && abs($porterage - $declared) >= 0.01) {
                $corrections[$key] = $porterage;
            }
        }

        if (!empty($corrections)) {
            $results = $this->requoteAtPorterage($items, $results, $corrections, $methodsByServiceId);
        }

        return $results;
    }

    /**
     * Packlink's carrier price for a shipment, from a products response entry.
     *
     * The rate a platform charges the shopper is Packlink's TOTAL price: `porterage` (the carrier)
     * plus `management_fee` (Packlink's own fee) - 33.81 + 0.99 = 34.80 on a measured order. Declaring
     * that total as the freight is wrong, because Packlink bills the duty on the carrier price alone.
     *
     * @param array $detail One `products_details` entry.
     *
     * @return float|null Null when the response does not carry it.
     */
    private function readPorterage(array $detail)
    {
        if (!isset($detail['products']['porterage']['total_price'])) {
            return null;
        }

        return round((float)$detail['products']['porterage']['total_price'], 2);
    }

    /**
     * Re-quotes the shipments whose declared freight was not Packlink's own carrier price.
     *
     * Customs value is goods plus freight, so the duty depends on which freight is declared - and the
     * two sides disagreed. A platform knows only Packlink's TOTAL price (its search API returns
     * basePrice and totalPrice identical, both 34.80), while Packlink bills the duty on `porterage`
     * alone, 33.81. Measured on a live order that cost 0.12 of over-charged duty: we quoted 122.45,
     * Packlink billed 122.33.
     *
     * The carrier price is only knowable from a products response, so this is a second pass rather
     * than a better first guess. Exactly one pass, never a loop: `porterage` is a property of the
     * service, route and parcel, not of the freight we declared, so re-quoting cannot move it.
     *
     * Only the affected shipments are re-quoted, and both waves stay concurrent, so the cost is one
     * extra round trip rather than one per carrier. The invoices are re-pointed with PUT, which is
     * also why no new ones are created.
     *
     * A failed correction leaves the first answer in place: it is the figure Packlink itself quoted,
     * so it is defensible, merely 0.12 generous to the merchant.
     *
     * @param array $items Original lookups.
     * @param array $results Results so far.
     * @param array $corrections Key => Packlink's carrier price.
     * @param array $methodsByServiceId Methods indexed by service id.
     *
     * @return array Results, with the corrected shipments replaced.
     */
    private function requoteAtPorterage(array $items, array $results, array $corrections, array $methodsByServiceId)
    {
        $invoiceRequests = array();

        foreach ($corrections as $key => $porterage) {
            if ($results[$key]['invoiceId'] === null) {
                continue;
            }

            $order = $items[$key]['order'];
            $order->setShippingCost($porterage);

            try {
                $invoice = $this->getCustomsService()->createCustomsInvoice($order);
            } catch (\Throwable $e) {
                continue;
            }

            if ($invoice === null) {
                continue;
            }

            $invoiceRequests[$key] = array(
                'method' => 'PUT',
                'url' => Proxy::BASE_URL . 'v2/customs-invoices/' . $results[$key]['invoiceId'],
                'body' => json_encode($invoice->toArray()),
                'timeout' => self::INVOICE_TIMEOUT_SECONDS,
            );
        }

        if (empty($invoiceRequests)) {
            return $results;
        }

        $productRequests = array();

        foreach ($this->requestConcurrently($invoiceRequests) as $key => $response) {
            if ($response['code'] < 200 || $response['code'] >= 300) {
                // The first answer stands.
                continue;
            }

            $request = $this->buildRequest($items[$key]['order'], $items[$key]['serviceId'], $results[$key]['invoiceId']);

            $productRequests[$key] = array(
                'method' => 'POST',
                'url' => Proxy::BASE_URL . 'pro/shipments/products',
                'body' => json_encode($request->toArray()),
                'timeout' => self::PRODUCTS_TIMEOUT_SECONDS,
            );
        }

        if (empty($productRequests)) {
            return $results;
        }

        foreach ($this->requestConcurrently($productRequests) as $key => $response) {
            if ($response['code'] < 200 || $response['code'] >= 300) {
                continue;
            }

            $decoded = json_decode($response['body'], true);
            $details = is_array($decoded) && isset($decoded['products_details'])
                ? $decoded['products_details']
                : array();

            if (empty($details[0]) || !is_array($details[0])) {
                continue;
            }

            $detail = DdpProductsDetail::fromArray($details[0]);

            if ($detail->ddpFee === null && $detail->customsAndDuties === null) {
                continue;
            }

            $results[$key]['costs'] = $this->buildResponse(
                $items[$key]['serviceId'],
                $detail,
                $methodsByServiceId
            );
        }

        return $results;
    }

    /**
     * Runs a set of requests concurrently and returns each one's status and body.
     *
     * Two waves of this replace what would otherwise be 2N sequential round trips. Every handle
     * carries its own timeout, which is how AC-7.4.1's sub-budgets are actually enforced - and in
     * parallel the callback's total is bounded by the slowest call rather than their sum, so the
     * budget holds however many carriers there are.
     *
     * One failed transfer never affects the others: a handle that errors comes back as code 0 and
     * the caller records it against that item only.
     *
     * @param array $requests Keyed requests, each with 'method', 'url', 'body' and 'timeout'.
     *
     * @return array Same keys, each an array of 'code' and 'body'.
     */
    private function requestConcurrently(array $requests)
    {
        $headers = $this->getParallelHeaders();
        $multi = curl_multi_init();
        $handles = array();

        foreach ($requests as $key => $request) {
            $handle = curl_init($request['url']);
            curl_setopt_array(
                $handle,
                array(
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => $request['method'],
                    CURLOPT_POSTFIELDS => $request['body'],
                    CURLOPT_TIMEOUT => $request['timeout'],
                    CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
                    CURLOPT_HTTPHEADER => $headers,
                )
            );

            curl_multi_add_handle($multi, $handle);
            $handles[$key] = $handle;
        }

        do {
            $status = curl_multi_exec($multi, $running);

            if ($running) {
                // Blocks until something moves, so the loop does not spin the CPU.
                curl_multi_select($multi, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        $responses = array();

        foreach ($handles as $key => $handle) {
            $error = curl_error($handle);
            $responses[$key] = array(
                'code' => (int)curl_getinfo($handle, CURLINFO_HTTP_CODE),
                'body' => (string)curl_multi_getcontent($handle),
            );

            if ($error !== '') {
                Logger::logWarning('DDP parallel request failed: ' . $error);
            }

            curl_multi_remove_handle($multi, $handle);
            curl_close($handle);
        }

        curl_multi_close($multi);

        return $responses;
    }

    /**
     * The headers a Packlink call carries.
     *
     * Duplicated from Proxy's own private builder because the concurrent path does not go through
     * Proxy. Six lines, and the alternative was a concurrent primitive on the shared HttpClient -
     * a change to infrastructure every integration depends on, for one DDP flow.
     *
     * @return string[]
     */
    private function getParallelHeaders()
    {
        $configuration = $this->getConfiguration();

        return array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: ' . $configuration->getAuthorizationToken(),
            'X-Module-Version: ' . $configuration->getModuleVersion(),
            'X-Ecommerce-Name: ' . $configuration->getECommerceName(),
            'X-Ecommerce-Version: ' . $configuration->getECommerceVersion(),
        );
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
     * @param array|null $methodsByServiceId Methods indexed by service id, when the caller already
     *                                       loaded them; null to load them here.
     *
     * @return DdpCostResponse
     *
     * @throws \Logeecom\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
     */
    // No `?array` hint on the last parameter: that syntax is PHP 7.1 and core targets 7.0, while the
    // implicit `array $x = null` form is deprecated in PHP 8.4. Untyped is the only spelling that is
    // clean on both ends of the supported range.
    private function buildResponse($serviceId, DdpProductsDetail $detail, $methodsByServiceId = null)
    {
        $response = new DdpCostResponse();
        $response->serviceId = $serviceId;
        $response->ddpFee = $detail->ddpFee;
        $response->customsAndDuties = $detail->customsAndDuties;

        // Passed in by the batch path, which loads the map once for the whole batch rather than
        // re-reading every shipping method per carrier.
        if ($methodsByServiceId === null) {
            $methodsByServiceId = $this->getMethodsByServiceId();
        }

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
