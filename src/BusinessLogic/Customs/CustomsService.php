<?php

namespace Packlink\BusinessLogic\Customs;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\ServiceRegister;
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
class CustomsService
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    const COMPANY = 'company';
    const BUSINESS = 'BUSINESS';
    const PRIVATE_PERSON = 'private_person';

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

        if (empty($warehouse->city) || empty($warehouse->address) || empty($warehouse->country)
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

        return $this->getProxy()->sendCustomsInvoice($customsInvoice);
    }

    /**
     * @param Order $shopOrder
     *
     * @return CustomsInvoice
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    protected function createCustomsInvoice($shopOrder)
    {
        $warehouse = $this->getWarehouse();
        $mapping = $this->getMapping();

        if (!$mapping) {
            return null;
        }

        $user = $this->getUser();

        $customsInvoice = new CustomsInvoice();
        $customsInvoice->invoiceNumber = $shopOrder->getId();
        $customsInvoice->sender = $this->getSender($warehouse, $user, $mapping);
        $customsInvoice->receiver = $this->getReceiver($shopOrder, $mapping);
        $customsInvoice->inventoriesOfContents = $this->getInventoryOfContents($shopOrder, $mapping);
        $customsInvoice->shipmentDetails = $this->getShipmentDetails($shopOrder);
        $customsInvoice->reasonForExport = $mapping->defaultReason;
        $customsInvoice->signature = $this->getSignature($warehouse);


        return $customsInvoice;
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
     *
     * @return ShipmentDetails
     */
    protected function getShipmentDetails(Order $order)
    {
        $shipmentDetails = new ShipmentDetails();
        $shipmentDetails->parcelsSize = 1;
        $shipmentDetails->parcelsWeight = $order->getTotalWeight();
        $cost = new Cost();
        $cost->currency = $order->getCurrency();
        $cost->value = $order->getTotalPrice();
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

        foreach ($order->getItems() as $item) {
            $inventory = new InventoryContent();
            $inventory->tariffNumber = $item->getTariffNumber() ?: $mapping->defaultTariffNumber;
            $inventory->description = $item->getTitle();
            $inventory->countryOfOrigin = $item->getCountryOfOrigin() ?: $mapping->defaultCountry;
            $itemValue = new Money();
            $itemValue->currency = $order->getCurrency();
            $itemValue->value = $item->getPrice();
            $inventory->itemValue = $itemValue;
            $inventory->itemWeight = $item->getWeight();
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
        $receiver = new Receiver();
        $receiver->userType = $mapping->defaultReceiverUserType;
        $receiver->fullName = $shopOrder->getShippingAddress()->getName() . ' ' . $shopOrder->getShippingAddress()->getSurname();
        $receiver->taxId = $mapping->defaultReceiverUserType === self::PRIVATE_PERSON ?
            ($shopOrder->getTaxId() ?: $mapping->defaultReceiverTaxId) : '';
        $receiver->companyName = $mapping->defaultReceiverUserType === self::COMPANY ?
            $shopOrder->getShippingAddress()->getCompany() : '';
        $receiver->vatNumber = $mapping->defaultReceiverUserType === self::COMPANY ?
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
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
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
