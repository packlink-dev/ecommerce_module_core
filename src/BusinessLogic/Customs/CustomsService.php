<?php

namespace Packlink\BusinessLogic\Customs;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\Http\DTO\Customs\Cost;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsInvoice;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsUnionsSearchRequest;
use Packlink\BusinessLogic\Http\DTO\Customs\InventoryContent;
use Packlink\BusinessLogic\Http\DTO\Customs\Money;
use Packlink\BusinessLogic\Http\DTO\Customs\Receiver;
use Packlink\BusinessLogic\Http\DTO\Customs\Sender;
use Packlink\BusinessLogic\Http\DTO\Customs\ShipmentDetails;
use Packlink\BusinessLogic\Http\DTO\Customs\Signature;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\Order\Interfaces\ShopOrderService;
use Packlink\BusinessLogic\Order\Objects\Order;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class CustomsService
 *
 * @package Packlink\BusinessLogic\Customs
 */
class CustomsService implements \Packlink\BusinessLogic\Customs\Interfaces\CustomsService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * user_type as the Packlink customs schema spells it: Enum: [PRIVATE_PERSON, COMPANY].
     * These values go on the wire, so they must match the enum exactly.
     */
    const COMPANY = 'COMPANY';
    const BUSINESS = 'BUSINESS';
    const PRIVATE_PERSON = 'PRIVATE_PERSON';

    /**
     * @var Warehouse
     */
    private $warehouse;
    /**
     * @var CustomsMapping
     */
    private $mapping;
    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * Checks if shipment is international.
     *
     * @param $countryCode
     * @param $postalCode
     *
     * @return bool
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function isShipmentInternational($countryCode, $postalCode)
    {
        $warehouse = $this->getWarehouse();
        $searchRequest = new CustomsUnionsSearchRequest();
        $searchRequest->fromCountryCode = $warehouse->country;
        $searchRequest->fromPostalCode = $warehouse->postalCode;
        $searchRequest->toCountryCode = $countryCode;
        $searchRequest->toPostalCode = $postalCode;

        $result = $this->getProxy()->getCustomsByPostalCode($searchRequest);

        return empty($result);
    }

    /**
     * @param $countryCode
     * @param $postalCode
     *
     * @return bool
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function shouldCreateCustoms($countryCode, $postalCode)
    {
        $warehouse = $this->getWarehouse();

        if (!$warehouse || empty($warehouse->city) || empty($warehouse->address) || empty($warehouse->country)
            || empty($warehouse->phone) || empty($warehouse->postalCode)
            || (empty($warehouse->name) && empty($warehouse->surname))) {
            return false;
        }

        return $this->isShipmentInternational($countryCode, $postalCode);
    }

    /**
     * Sends customs invoice.
     *
     * @param Order $order
     *
     * @return string|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function sendCustomsInvoice($order)
    {
        $customsInvoice = $this->createCustomsInvoice($order);

        if (!$customsInvoice) {
            return null;
        }

        return $this->getProxy()->sendCustomsInvoice($customsInvoice);
    }

    /**
     * @param Order $shopOrder
     *
     * @return CustomsInvoice|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function createCustomsInvoice($shopOrder)
    {
        $warehouse = $this->getWarehouse();
        $mapping = $this->getMapping();

        if (!$mapping || !$warehouse) {
            return null;
        }

        $user = $this->getUser();

        $inventoriesOfContents = $this->getInventoryOfContents($shopOrder, $mapping);

        if (!$this->isInventoryComplete($shopOrder, $inventoriesOfContents)) {
            return null;
        }

        $customsInvoice = new CustomsInvoice();
        $customsInvoice->invoiceNumber = $shopOrder->getOrderNumber();
        $customsInvoice->sender = $this->getSender($warehouse, $user, $mapping);
        $customsInvoice->receiver = $this->getReceiver($shopOrder, $mapping);
        $customsInvoice->inventoriesOfContents = $inventoriesOfContents;
        $customsInvoice->shipmentDetails = $this->getShipmentDetails($shopOrder, $inventoriesOfContents);
        $customsInvoice->reasonForExport = strtoupper($mapping->defaultReason);
        $customsInvoice->signature = $this->getSignature($warehouse);


        return $customsInvoice;
    }

    /**
     * Checks that every inventory item resolved the customs-required values (tariff
     * number, country of origin and weight, from the item or the configured fallbacks).
     * An invoice with an empty required field must be skipped - the draft then proceeds
     * without customs - rather than sent invalid.
     *
     * @param Order $shopOrder
     * @param InventoryContent[] $inventoriesOfContents
     *
     * @return bool
     */
    protected function isInventoryComplete($shopOrder, array $inventoriesOfContents)
    {
        foreach ($inventoriesOfContents as $inventory) {
            if (empty($inventory->tariffNumber)) {
                Logger::logWarning(
                    'Customs invoice skipped for order ' . $shopOrder->getOrderNumber()
                    . ': no tariff number resolved for item "' . $inventory->description
                    . '" and no default tariff number is configured.'
                );

                return false;
            }

            if (empty($inventory->countryOfOrigin)) {
                Logger::logWarning(
                    'Customs invoice skipped for order ' . $shopOrder->getOrderNumber()
                    . ': no country of origin resolved for item "' . $inventory->description
                    . '" and no default country of origin is configured.'
                );

                return false;
            }

            if (empty($inventory->itemWeight)) {
                Logger::logWarning(
                    'Customs invoice skipped for order ' . $shopOrder->getOrderNumber()
                    . ': no weight resolved for item "' . $inventory->description
                    . '" and the default parcel has no weight either.'
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @param Warehouse $warehouse
     *
     * @return Signature
     */
    protected function getSignature(Warehouse $warehouse)
    {
        $signature = new Signature();

        $signature->fullName = $warehouse->name . ' ' . $warehouse->surname;
        $signature->city = $warehouse->city;

        return $signature;
    }

    /**
     * @param Order $order
     * @param InventoryContent[] $inventoriesOfContents Items with their weights already resolved.
     *
     * @return ShipmentDetails
     */
    protected function getShipmentDetails(Order $order, array $inventoriesOfContents = array())
    {
        $shipmentDetails = new ShipmentDetails();
        $shipmentDetails->parcelsSize = 1;
        // The API rejects a shipment weighing nothing ("parcels_weight ... expected more than 0"),
        // and the platform's total is the sum of the products' own weights - zero for a catalogue
        // that does not set them. Fall back to the weights the inventory already resolved, which
        // carry the default parcel for exactly those products.
        $shipmentDetails->parcelsWeight = $order->getTotalWeight()
            ?: $this->getInventoryWeight($inventoriesOfContents);
        $cost = new Cost();
        $cost->currency = $order->getCurrency();
        // The API expects the shipment (freight) cost here; customs value is goods + freight, so
        // sending the goods value double-counts the goods and inflates every duty quote (C8). Fall
        // back to the old behaviour only when the platform has not supplied the freight.
        $cost->value = $order->getShippingCost() !== null
            ? $order->getShippingCost()
            : $order->getTotalPrice();
        $shipmentDetails->cost = $cost;

        return $shipmentDetails;
    }

    /**
     * @param Order $order
     * @param CustomsMapping $mapping
     *
     * @return array
     */
    protected function getInventoryOfContents(Order $order, CustomsMapping $mapping)
    {
        $result = array();
        $defaultWeight = $this->getDefaultParcelWeight();

        foreach ($order->getItems() as $item) {
            $inventory = new InventoryContent();
            $inventory->tariffNumber = $item->getTariffNumber() ?: $mapping->defaultTariffNumber;
            $inventory->description = $item->getTitle();
            $inventory->countryOfOrigin = $item->getCountryOfOrigin() ?: $mapping->defaultCountry;
            $itemValue = new Money();
            $itemValue->currency = $order->getCurrency();
            $itemValue->value = $item->getPrice();
            $inventory->itemValue = $itemValue;
            $inventory->itemWeight = $item->getWeight() ?: $defaultWeight;
            $inventory->quantity = $item->getQuantity();

            $result[] = $inventory;
        }

        return $result;
    }

    /**
     * @param Order $shopOrder
     * @param CustomsMapping $mapping
     *
     * @return Receiver
     */
    protected function getReceiver(Order $shopOrder, CustomsMapping $mapping)
    {
        // Mappings saved before the form carried the Packlink-cased tokens hold lowercase values.
        // CustomsMapping normalizes on read, but a platform can also set the DTO by hand, so
        // upper-case here too - the enum is what goes on the wire.
        $receiverUserType = strtoupper($mapping->defaultReceiverUserType);

        $receiver = new Receiver();
        $receiver->userType = $receiverUserType;
        $receiver->fullName = $shopOrder->getShippingAddress()->getName() . ' ' . $shopOrder->getShippingAddress()->getSurname();
        $receiver->taxId = $receiverUserType === self::PRIVATE_PERSON ?
            ($shopOrder->getTaxId() ?: $mapping->defaultReceiverTaxId) : '';
        $receiver->companyName = $receiverUserType === self::COMPANY ?
            $shopOrder->getShippingAddress()->getCompany() : '';
        $receiver->vatNumber = $receiverUserType === self::COMPANY ?
            ($shopOrder->getVatNumber() ?: $mapping->defaultReceiverTaxId) : '';
        $receiver->address = $shopOrder->getShippingAddress()->getStreet1() . ' ' .
            $shopOrder->getShippingAddress()->getStreet2();
        $receiver->postalCode = $shopOrder->getShippingAddress()->getZipCode();
        $receiver->city = $shopOrder->getShippingAddress()->getCity();
        $receiver->country = $shopOrder->getShippingAddress()->getCountry();
        $receiver->phoneNumber = $shopOrder->getShippingAddress()->getPhone();

        return $receiver;
    }

    /**
     * @param Warehouse $warehouse
     * @param User $user
     * @param CustomsMapping $mapping
     *
     * @return Sender
     */
    protected function getSender(Warehouse $warehouse, User $user, CustomsMapping $mapping)
    {
        $sender = new Sender();
        $sender->userType = $user->customerType === self::BUSINESS ? self::COMPANY : self::PRIVATE_PERSON;
        $sender->fullName = $warehouse->name . ' ' . $warehouse->surname;
        $sender->taxId = $sender->userType === self::PRIVATE_PERSON ? $mapping->defaultSenderTaxId : '';
        $sender->companyName = $sender->userType === self::COMPANY ? $warehouse->company : '';
        $sender->vatNumber = $sender->userType === self::COMPANY ? $mapping->defaultSenderTaxId : '';
        $sender->address = $warehouse->address;
        $sender->postalCode = $warehouse->postalCode;
        $sender->city = $warehouse->city;
        $sender->country = $warehouse->country;
        $sender->phoneNumber = $warehouse->phone;

        return $sender;
    }

    /**
     * @return User|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function getUser()
    {
        $user = $this->getConfigService()->getUserInfo();

        if (empty($user) || empty($user->customerType)) {
            $user = $this->getProxy()->getUserData();
            $this->getConfigService()->setUserInfo($user);
        }

        return $user;
    }

    /**
     * Total weight of the resolved inventory, used when the platform reports none for the order.
     *
     * @param InventoryContent[] $inventoriesOfContents
     *
     * @return float
     */
    protected function getInventoryWeight(array $inventoriesOfContents)
    {
        $total = 0.0;

        foreach ($inventoriesOfContents as $inventory) {
            $quantity = $inventory->quantity ? (int)$inventory->quantity : 1;
            $total += (float)$inventory->itemWeight * $quantity;
        }

        return $total;
    }

    /**
     * Weight to declare for a product that carries none of its own.
     *
     * The schema requires item_weight, and the store has already answered "what does a typical
     * package weigh" once, when it configured the default parcel - so customs reuses that answer
     * instead of asking the merchant for a second number that would inevitably drift from it.
     *
     * @return float Default parcel weight, or 0 when no default parcel is configured.
     */
    protected function getDefaultParcelWeight()
    {
        $parcel = $this->getConfigService()->getDefaultParcel();

        return $parcel ? (float)$parcel->weight : 0.0;
    }

    /**
     * @return Warehouse|null
     */
    protected function getWarehouse()
    {
        if ($this->warehouse === null) {
            $this->warehouse = $this->getConfigService()->getDefaultWarehouse();
        }

        return $this->warehouse;
    }

    /**
     * @return CustomsMapping|null
     */
    protected function getMapping()
    {
        if ($this->mapping === null) {
            $this->mapping = $this->getConfigService()->getCustomsMappings();
        }

        return $this->mapping;
    }

    /**
     * @return \Packlink\BusinessLogic\Configuration
     */
    protected function getConfigService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * @return Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(\Packlink\BusinessLogic\Http\Interfaces\Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * @return ShopOrderService
     */
    protected function getShopOrderService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(ShopOrderService::CLASS_NAME);
    }
}
