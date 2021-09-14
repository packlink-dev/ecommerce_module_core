<?php

namespace Packlink\BusinessLogic\Order;

use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\DTO\ShipmentLabel;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Exceptions\EmptyOrderException;
use Packlink\BusinessLogic\Order\Exceptions\OrderNotFound;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound;
use Packlink\BusinessLogic\OrderShipmentDetails\OrderShipmentDetailsService;
use Packlink\BusinessLogic\ShippingMethod\PackageTransformer;
use Packlink\BusinessLogic\ShippingMethod\ShippingCostCalculator;
use Packlink\BusinessLogic\ShippingMethod\ShippingMethodService;
use Packlink\BusinessLogic\ShippingMethod\Utility\ShipmentStatus;

/**
 * Class OrderService.
 *
 * @package Packlink\BusinessLogic\Order
 */
class OrderService extends BaseService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Singleton instance of this class.
     *
     * @var static
     */
    protected static $instance;
    /**
     * Configuration service.
     *
     * @var Configuration
     */
    private $configuration;
    /**
     * @var ShopOrderService
     */
    private $shopOrderService;
    /**
     * @var OrderShipmentDetailsService
     */
    private $orderShipmentDetailsService;

    /**
     * OrderService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->shopOrderService = ServiceRegister::getService(ShopOrderService::CLASS_NAME);
        $this->orderShipmentDetailsService = ServiceRegister::getService(OrderShipmentDetailsService::CLASS_NAME);
        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Prepares shipment draft object for order with provided unique identifier.
     *
     * @param string $orderId Unique order id.
     *
     * @return Draft Prepared shipment draft.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     * @throws \Packlink\BusinessLogic\Order\Exceptions\EmptyOrderException When order has no items.
     */
    public function prepareDraft($orderId)
    {
        $order = $this->shopOrderService->getOrderAndShippingData($orderId);

        $items = $order->getItems();
        if (empty($items)) {
            throw new EmptyOrderException("Order [$orderId] has no order items.");
        }

        return $this->convertOrderToDraftDto($order);
    }

    /**
     * Sets order packlink reference number.
     *
     * @param string $orderId Unique order id.
     * @param string $shipmentReference Packlink shipment reference.
     */
    public function setReference($orderId, $shipmentReference)
    {
        $this->orderShipmentDetailsService->setReference($orderId, $shipmentReference);
    }

    /**
     * @param \Packlink\BusinessLogic\Http\DTO\Shipment $shipment
     *
     * @throws \Packlink\BusinessLogic\OrderShipmentDetails\Exceptions\OrderShipmentDetailsNotFound
     */
    public function updateShipmentData(Shipment $shipment)
    {
        /** @var OrderShipmentDetailsService $shipmentDetailsService */
        $this->orderShipmentDetailsService->setShippingPrice(
            $shipment->reference,
            (float)$shipment->price,
            $shipment->currency
        );
        $this->updateShippingStatus($shipment, ShipmentStatus::getStatus($shipment->status));

        if ($this->isTrackingInfoUpdatable($shipment->status)) {
            $this->updateTrackingInfo($shipment);
        }
    }

    /**
     * Updates shipping status from API for order with given shipment reference.
     *
     * @param Shipment $shipment Shipment DTO for given reference number.
     * @param string $status Shipping status.
     */
    public function updateShippingStatus(Shipment $shipment, $status)
    {
        $e = null;
        try {
            $orderShipmentDetails = $this->orderShipmentDetailsService->getDetailsByReference($shipment->reference);

            if ($orderShipmentDetails === null) {
                throw new OrderShipmentDetailsNotFound(
                    'Order details not found for reference "' . $shipment->reference . '".'
                );
            }

            $this->orderShipmentDetailsService->setShippingStatus($shipment->reference, $status);
            $this->shopOrderService->updateShipmentStatus($orderShipmentDetails->getOrderId(), $status);
        } catch (OrderNotFound $e) {
        } catch (OrderShipmentDetailsNotFound $e) {
        }

        if ($e !== null) {
            Logger::logWarning(
                $e->getMessage(),
                'Core',
                array('referenceId' => $shipment->reference, 'status' => $status)
            );
        }
    }

    /**
     * Updates tracking info from API for order with given shipment reference.
     *
     * @param Shipment $shipment Shipment DTO for given reference number.
     */
    public function updateTrackingInfo(Shipment $shipment)
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        try {
            $orderShipmentDetails = $this->orderShipmentDetailsService->getDetailsByReference($shipment->reference);

            if ($orderShipmentDetails === null) {
                throw new OrderShipmentDetailsNotFound(
                    'Order details not found for reference "' . $shipment->reference . '".'
                );
            }

            $this->orderShipmentDetailsService->setTrackingInfo(
                $shipment->reference,
                $shipment->carrierTrackingUrl,
                $shipment->trackingCodes ?: array()
            );

            $trackingHistory = $proxy->getTrackingInfo($shipment->reference);
            $this->shopOrderService->updateTrackingInfo(
                $orderShipmentDetails->getOrderId(),
                $shipment,
                $trackingHistory
            );
        } catch (HttpBaseException $e) {
            Logger::logWarning($e->getMessage(), 'Core', array(
                'referenceId' => $shipment->reference,
                'trace' => $e->getTraceAsString(),
            ));
        } catch (OrderShipmentDetailsNotFound $e) {
            Logger::logInfo($e->getMessage(), 'Core', array('referenceId' => $shipment->reference));
        } catch (OrderNotFound $e) {
            Logger::logInfo($e->getMessage(), 'Core', array('referenceId' => $shipment->reference));
        }
    }

    /**
     * Retrieves list of order labels.
     *
     * @param string $reference Order reference.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\ShipmentLabel[] List of shipment labels for an order defined by
     *      the provided reference.
     */
    public function getShipmentLabels($reference)
    {
        /** @var Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $labels = array();

        try {
            $links = $proxy->getLabels($reference);
            foreach ($links as $link) {
                $labels[] = new ShipmentLabel($link);
            }
        } catch (\Exception $e) {
            Logger::logError("Failed to retrieve labels for order [$reference] because: {$e->getMessage()}");
        }

        return $labels;
    }

    /**
     * Checks whether shipment labels are available.
     *
     * @param string $status Shipment status.
     *
     * @return bool TRUE if shipment labels are available; FALSE otherwise.
     */
    public function isReadyToFetchShipmentLabels($status)
    {
        return in_array(
            $status,
            array(
                ShipmentStatus::STATUS_READY,
                ShipmentStatus::STATUS_IN_TRANSIT,
                ShipmentStatus::OUT_FOR_DELIVERY,
                ShipmentStatus::STATUS_DELIVERED,
            ),
            true
        );
    }

    /**
     * Checks if tracking info should be updated.
     *
     * @param string $status Shipment status.
     *
     * @return bool TRUE if tracking info should be update; FALSE otherwise.
     */
    public function isTrackingInfoUpdatable($status)
    {
        $allowedUpdateStatuses = array(
            ShipmentStatus::STATUS_ACCEPTED,
            ShipmentStatus::STATUS_READY,
            ShipmentStatus::STATUS_IN_TRANSIT,
            ShipmentStatus::OUT_FOR_DELIVERY,
        );

        return in_array(ShipmentStatus::getStatus($status), $allowedUpdateStatuses, true);
    }

    /**
     * Converts order object to draft DTO suitable for sending to Packlink.
     *
     * @param Order $order Order object.
     *
     * @return Draft Prepared shipment draft.
     */
    private function convertOrderToDraftDto(Order $order)
    {
        $user = $this->configuration->getUserInfo();

        $draft = new Draft();
        $draft->contentValueCurrency = $order->getCurrency();
        $draft->contentValue = $order->getTotalPrice();
        $draft->priority = $order->isHighPriority();
        $draft->source = $this->configuration->getDraftSource();
        $draft->shipmentCustomReference = $order->getOrderNumber();
        $this->addPackages($order, $draft);

        $methodId = $order->getShippingMethodId();
        if ($methodId !== null) {
            $this->addServiceDetails($draft, $order, $methodId);
        }

        $draft->dropOffPointId = $order->getShippingDropOffId();
        if ($user) {
            $draft->platformCountry = $user->country;
        }

        $this->addDepartureAddress($draft);
        $this->addDestinationAddress($order, $draft);
        $this->addAdditionalData($order, $draft);

        return $draft;
    }

    /**
     * Adds shipping service details to draft.
     *
     * @param Draft $draft Draft object to set data to.
     * @param Order $order Order object to get data from.
     * @param int $methodId Id of the shipping method.
     */
    private function addServiceDetails(Draft $draft, Order $order, $methodId)
    {
        /** @var ShippingMethodService $shippingService */
        $shippingService = ServiceRegister::getService(ShippingMethodService::CLASS_NAME);
        $shippingMethod = $shippingService->getShippingMethod($methodId);
        if ($shippingMethod !== null) {
            try {
                /** @var \Packlink\BusinessLogic\Warehouse\Warehouse $warehouse */
                $warehouse = $this->configuration->getDefaultWarehouse();
                $address = $order->getShippingAddress();
                $service = ShippingCostCalculator::getCheapestShippingService(
                    $shippingMethod,
                    $warehouse->country,
                    $warehouse->postalCode,
                    $address->getCountry(),
                    $address->getZipCode(),
                    $draft->packages
                );
                $draft->serviceId = $service->serviceId;
                $draft->serviceName = $shippingMethod->getTitle();
                $draft->carrierName = $shippingMethod->getCarrierName();
            } catch (\InvalidArgumentException $e) {
                Logger::logWarning(
                    "Invalid service method $methodId selected for order " . $order->getId()
                    . ' because this method does not support order\'s destination country.'
                    . ' Sending order without selected method.'
                );
            }
        }
    }

    /**
     * Adds destination address to draft shipment.
     *
     * @param Order $order Shop order.
     * @param Draft $draft Packlink shipment draft.
     */
    private function addDestinationAddress(Order $order, Draft $draft)
    {
        $to = $order->getShippingAddress();
        $draft->to = new Draft\Address();
        $draft->to->country = $to->getCountry();
        $draft->to->zipCode = $to->getZipCode();
        $draft->to->email = $to->getEmail();
        $draft->to->name = $to->getName();
        $draft->to->surname = $to->getSurname();
        $draft->to->city = $to->getCity();
        $draft->to->company = $to->getCompany();
        $draft->to->phone = $to->getPhone();
        $draft->to->street1 = $to->getStreet1();
        $draft->to->street2 = $to->getStreet2();
    }

    /**
     * Adds source address to draft shipment from default warehouse.
     *
     * @param Draft $draft Packlink shipment draft.
     */
    private function addDepartureAddress(Draft $draft)
    {
        /** @var \Packlink\BusinessLogic\Warehouse\Warehouse $warehouse */
        $warehouse = $this->configuration->getDefaultWarehouse();
        $draft->from = new Draft\Address();
        $draft->from->country = $warehouse->country;
        $draft->from->zipCode = $warehouse->postalCode;
        $draft->from->email = $warehouse->email;
        $draft->from->name = $warehouse->name;
        $draft->from->surname = $warehouse->surname;
        $draft->from->city = $warehouse->city;
        $draft->from->company = $warehouse->company;
        $draft->from->phone = $warehouse->phone;
        $draft->from->street1 = $warehouse->address;
    }

    /**
     * Adds additional data to draft shipment.
     *
     * @param Order $order Shop order.
     * @param Draft $draft Packlink shipment draft.
     */
    private function addAdditionalData(Order $order, Draft $draft)
    {
        $additional = new Draft\AdditionalData();
        $additional->selectedWarehouseId = $this->configuration->getDefaultWarehouse()->id;
        $additional->orderId = $order->getId();
        $additional->sellerUserId = $order->getSellerUserId();

        $additional->items = array();
        foreach ($order->getItems() as $item) {
            $draftItem = new Draft\DraftItem();
            $draftItem->price = $item->getTotalPrice();
            $draftItem->categoryName = $item->getCategoryName();
            $draftItem->pictureUrl = $item->getPictureUrl();
            $draftItem->title = $item->getTitle();
            $draftItem->quantity = $item->getQuantity();

            $additional->items[] = $draftItem;
        }

        $draft->additionalData = $additional;
    }

    /**
     * Adds item packages and set content to draft shipment.
     *
     * @param Order $order Shop order.
     * @param Draft $draft Packlink shipment draft.
     */
    private function addPackages(Order $order, Draft $draft)
    {
        $draft->content = array();
        $packages = array();
        foreach ($order->getItems() as $item) {
            $quantity = $item->getQuantity() ?: 1;
            $draft->content[] = $quantity . ' ' . $item->getTitle();
            for ($i = 0; $i < $quantity; $i++) {
                $packages[] = new Package(
                    $item->getWeight(),
                    $item->getWidth(),
                    $item->getHeight(),
                    $item->getLength()
                );
            }
        }

        /** @var PackageTransformer $transformer */
        $transformer = ServiceRegister::getService(PackageTransformer::CLASS_NAME);
        $draft->packages = array($transformer->transform($packages));
    }
}
