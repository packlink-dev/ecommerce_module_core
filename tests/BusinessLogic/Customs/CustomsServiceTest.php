<?php

namespace Logeecom\Tests\BusinessLogic\Customs;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestFrontDtoFactory;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Order\TestShopOrderService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Customs\CustomsService;
use Packlink\BusinessLogic\Customs\Models\CustomsMapping;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsInvoice;
use Packlink\BusinessLogic\Http\DTO\User;

/**
 * Class CustomsServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\Customs
 */
class CustomsServiceTest extends BaseTestWithServices
{
    /**
     * @var CustomsService
     */
    private $customsService;

    /**
     * @before
     *
     * @return void
     *
     * @throws \Packlink\BusinessLogic\DTO\Exceptions\FrontDtoFactoryRegistrationException
     */
    public function before()
    {
        parent::before();

        $this->customsService = new CustomsService();
        $me = $this;

        TestServiceRegister::registerService(
            CustomsService::CLASS_NAME,
            function () use ($me) {
                return $me->customsService;
            }
        );

        TestFrontDtoFactory::register(CustomsMapping::CLASS_KEY, CustomsMapping::CLASS_NAME);

        $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        $user = new User();
        $user->customerType = 'BUSINESS';
        $this->shopConfig->setUserInfo($user);
    }

    /**
     * The direct call exercises the public service surface the checkout DDP flow relies on
     * (building an invoice without sending it).
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testCreateCustomsInvoiceReturnsPopulatedInvoice()
    {
        $this->shopConfig->setCustomsMappings($this->getCustomsMapping());
        $shopOrderService = new TestShopOrderService();
        $order = $shopOrderService->getOrder('customs-service-test', 1, 'DE');

        $invoice = $this->customsService->createCustomsInvoice($order);

        self::assertInstanceOf(CustomsInvoice::class, $invoice);
        self::assertSame('testOrderNumber', $invoice->invoiceNumber);
        self::assertSame('PURCHASE_OR_SALE', $invoice->reasonForExport);
        self::assertNotNull($invoice->sender);
        self::assertNotNull($invoice->receiver);
        self::assertCount(1, $invoice->inventoriesOfContents);
    }

    /**
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Order\Exceptions\OrderNotFound
     */
    public function testCreateCustomsInvoiceWithoutMapping()
    {
        $shopOrderService = new TestShopOrderService();
        $order = $shopOrderService->getOrder('customs-service-test-no-mapping', 1, 'DE');

        self::assertNull($this->customsService->createCustomsInvoice($order));
    }

    /**
     * @return CustomsMapping
     */
    private function getCustomsMapping()
    {
        $mapping = new CustomsMapping();
        $mapping->defaultReason = 'PURCHASE_OR_SALE';
        $mapping->defaultSenderTaxId = '123';
        $mapping->defaultReceiverUserType = 'PRIVATE_PERSON';
        $mapping->defaultReceiverTaxId = '123';
        $mapping->defaultTariffNumber = '0123456';
        $mapping->defaultCountry = 'FR';
        $mapping->mappingReceiverTaxId = 'tax_1';

        return $mapping;
    }
}
