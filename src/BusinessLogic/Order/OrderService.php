<?php

namespace Packlink\BusinessLogic\Order;

use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\BaseService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Order\Interfaces\OrderRepository;
use Packlink\BusinessLogic\Order\Objects\Order;

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
     * Order repository instance.
     *
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * OrderService constructor.
     */
    protected function __construct()
    {
        parent::__construct();

        $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        $this->orderRepository = ServiceRegister::getService(OrderRepository::CLASS_NAME);
    }

    /**
     * Prepares shipment draft object for order with provided unique identifier.
     *
     * @param string $orderId Unique order id.
     *
     * @return Draft Prepared shipment draft.
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     */
    public function prepareDraft($orderId)
    {
        $order = $this->orderRepository->getOrderAndShippingData($orderId);

        return $this->convertOrderToDraftDto($order);
    }

    /**
     * Sets order packlink reference number.
     *
     * @param string $orderId Unique order id.
     * @param string $shipmentReference Packlink shipment reference.
     *
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound When order with provided id is not found.
     */
    public function setReference($orderId, $shipmentReference)
    {
        $this->orderRepository->setReference($orderId, $shipmentReference);
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
        $draft->source = 'source_inbound';

        $shipping = $order->getShipping();
        if ($shipping !== null) {
            $draft->serviceId = $shipping->getShippingServiceId();
            $draft->serviceName = $shipping->getShippingServiceName();
            $draft->carrierName = $shipping->getCarrierName();
        }

        $draft->dropOffPointId = $order->getShippingDropOffId();
        if ($user) {
            $draft->platformCountry = $user->country;
        }

        $this->addDepartureAddress($draft);
        $this->addDestinationAddress($order, $draft);
        $this->addAdditionalData($order, $draft);
        $this->addPackages($order, $draft);

        return $draft;
    }

    /**
     * Adds destination address to draft shipment.
     *
     * @param Order $order Shop order.
     * @param Draft $draft Packlink shipment draft.
     */
    private function addDestinationAddress(Order $order, Draft $draft)
    {
        $to = $order->getShippingDropOffId() ? $order->getShippingDropOffAddress() : $order->getShippingAddress();
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
        /** @var \Packlink\BusinessLogic\Http\DTO\Warehouse $warehouse */
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
        if ($order->getShipping() !== null) {
            $additional->shippingServiceName = $order->getShipping()->getShippingServiceName();
        }

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
        $content = array();
        $draft->packages = array();
        foreach ($order->getItems() as $item) {
            $quantity = $item->getQuantity() ?: 1;
            $content[] = $quantity . ' ' . $item->getTitle();
            for ($i = 0; $i < $quantity; $i++) {
                $package = new \Packlink\BusinessLogic\Http\DTO\Package();
                $package->height = $item->getHeight();
                $package->width = $item->getWidth();
                $package->length = $item->getLength();
                $package->weight = $item->getWeight();

                $draft->packages[] = $package;
            }
        }

        $draft->content = implode('; ', $content);
    }
}
