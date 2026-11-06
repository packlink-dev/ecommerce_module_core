<?php

namespace Logeecom\Tests\BusinessLogic\SimulatedPrices;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Brand\DTO\BrandConfiguration;
use Packlink\BusinessLogic\Controllers\SimulatedPricesController;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\SimulatedPrices\Interfaces\ShopOriginProvider;
use Packlink\BusinessLogic\SimulatedPrices\SimulatedPricesService;
use Packlink\BusinessLogic\Subscription\SubscriptionService;

/**
 * Class SimulatedPricesControllerTest.
 *
 * @package Logeecom\Tests\BusinessLogic\SimulatedPrices
 */
class SimulatedPricesControllerTest extends BaseTestWithServices
{
    /**
     * @before
     * @inheritDoc
     */
    public function before()
    {
        parent::before();

        $shopShippingMethodService = new TestShopShippingMethodService();
        TestServiceRegister::registerService(
            ShopShippingMethodService::CLASS_NAME,
            function () use ($shopShippingMethodService) {
                return $shopShippingMethodService;
            }
        );
        TestServiceRegister::registerService(
            SubscriptionService::CLASS_NAME,
            function () {
                return SubscriptionService::getInstance();
            }
        );
        TestServiceRegister::registerService(
            SimulatedPricesService::CLASS_NAME,
            function () {
                return SimulatedPricesService::getInstance();
            }
        );
    }

    /**
     * @after
     * @inheritDoc
     */
    public function after()
    {
        SimulatedPricesService::resetInstance();
        SubscriptionService::resetInstance();
        parent::after();
    }

    public function testGetDataWithoutAccountMakesNoCall()
    {
        $this->shopConfig->setAuthorizationToken('');

        $data = $this->getController()->getData();

        $this->assertEmpty($this->httpClient->getHistory());
        $this->assertFalse($data['connected']);
        $this->assertTrue($data['enabled']);
        $this->assertNull($data['currentTier']);
        $this->assertNull($data['upgradeUrl']);
        $this->assertEquals('ES', $data['defaultMarket']);
        $this->assertCount(5, $data['markets']);
        $this->assertEquals(array('length' => 15, 'width' => 10, 'height' => 3, 'weight' => 0.5), $data['parcel']);
    }

    public function testGetDataConnectedFreeMerchant()
    {
        $this->setMerchantCountry('FR');
        $this->httpClient->setMockResponses(array($this->subscriptionResponse('Free')));

        $data = $this->getController()->getData();

        $this->assertTrue($data['connected']);
        $this->assertTrue($data['enabled']);
        $this->assertEquals('FREE', $data['currentTier']);
        $this->assertEquals('https://pro.packlink.fr/private/subscriptions', $data['upgradeUrl']);
        $this->assertCount(1, $this->httpClient->getHistory());
    }

    public function testGetDataConnectedPremiumMerchantIsDisabled()
    {
        $this->httpClient->setMockResponses(array($this->subscriptionResponse('Premium')));

        $data = $this->getController()->getData();

        $this->assertFalse($data['enabled']);
        $this->assertEquals('PREMIUM', $data['currentTier']);
    }

    public function testSearchDelegatesToService()
    {
        $this->shopConfig->setAuthorizationToken('');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));

        $result = $this->getController()->search(array('market' => 'it', 'toPostalCode' => ' 00118 '));

        $this->assertEquals('no_services', $result['error']);
        $this->assertEquals('IT', $result['market']);
        $this->assertTrue(strpos($this->httpClient->getLastRequest()['url'], 'to%5Bzip%5D=00118') !== false);
    }

    public function testSearchIgnoresNonScalarInput()
    {
        $this->shopConfig->setAuthorizationToken('');

        $result = $this->getController()->search(array('market' => array('ES')));

        $this->assertEquals('validation', $result['error']);
        $this->assertEmpty($this->httpClient->getHistory());
    }

    public function testGetConfigurationMakesNoCall()
    {
        $configuration = $this->getController()->getConfiguration();

        $this->assertEmpty($this->httpClient->getHistory());
        $this->assertTrue($configuration['enabled']);
        $this->assertEquals('ES', $configuration['defaultMarket']);
        $this->assertCount(5, $configuration['markets']);
        $this->assertEquals(array('length' => 15, 'width' => 10, 'height' => 3, 'weight' => 0.5), $configuration['parcel']);
    }

    public function testAccountMarketIsTheAccountCountryOfAConnectedMerchant()
    {
        $this->setMerchantCountry('fr');

        $this->assertEquals('FR', $this->getController()->getConfiguration()['accountMarket']);
        $this->assertEmpty($this->httpClient->getHistory());
    }

    public function testAccountMarketIsEmptyWithoutAccountOrOutsideTheMarkets()
    {
        $this->setMerchantCountry('NL');
        $this->assertNull($this->getController()->getConfiguration()['accountMarket']);

        $this->shopConfig->setAuthorizationToken('');
        $this->setMerchantCountry('FR');
        $this->assertNull($this->getController()->getConfiguration()['accountMarket']);
    }

    public function testOtherPlatformIsNotEnabled()
    {
        $this->setPlatformCode('OTHER');
        $this->shopConfig->setAuthorizationToken('');

        $this->assertFalse($this->getController()->getConfiguration()['enabled']);
        $this->assertFalse($this->getController()->getData()['enabled']);
        $result = $this->getController()->search(array('market' => 'ES'));

        $this->assertEquals('not_enabled', $result['error']);
        $this->assertEmpty($this->httpClient->getHistory());
    }

    public function testSearchWithoutAccountTakesOriginFromShopNotFromPayload()
    {
        $this->shopConfig->setAuthorizationToken('');
        $provider = $this->registerOriginProvider(array('country' => 'de', 'postalCode' => ' 10115 '));
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));

        $this->getController()->search(
            array('market' => 'DE', 'fromCountry' => 'FR', 'fromPostalCode' => '75001')
        );

        $this->assertEquals(1, $provider->calls);
        $query = $this->getLastQuery();
        $this->assertEquals(array('country' => 'DE', 'zip' => '10115'), $query['from']);
    }

    public function testSearchWithoutAccountIgnoresPayloadOriginWithoutProvider()
    {
        $this->shopConfig->setAuthorizationToken('');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));

        $this->getController()->search(
            array('market' => 'ES', 'fromCountry' => 'FR', 'fromPostalCode' => '75001')
        );

        $this->assertEquals(array('country' => 'ES', 'zip' => '28001'), $this->getLastQuery()['from']);
    }

    public function testSearchWithoutAccountFallsBackToCapitalWhenShopOriginFails()
    {
        $this->shopConfig->setAuthorizationToken('');
        $provider = $this->registerOriginProvider(null);
        $provider->exception = new \RuntimeException('Shop API down');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));

        $result = $this->getController()->search(array('market' => 'IT'));

        $this->assertEquals('no_services', $result['error']);
        $this->assertEquals(array('country' => 'IT', 'zip' => '00118'), $this->getLastQuery()['from']);
    }

    public function testConnectedSearchDoesNotAskShopOrigin()
    {
        $provider = $this->registerOriginProvider(array('country' => 'DE', 'postalCode' => '10115'));
        $this->httpClient->setMockResponses(array($this->subscriptionResponse('Free')));

        $this->getController()->search(array('market' => 'ES'));

        $this->assertEquals(0, $provider->calls);
    }

    public function testHttpStatus()
    {
        $this->assertEquals(200, SimulatedPricesController::getHttpStatus(null));
        $this->assertEquals(200, SimulatedPricesController::getHttpStatus('no_services'));
        $this->assertEquals(403, SimulatedPricesController::getHttpStatus('not_enabled'));
        foreach (array('validation', 'premium', 'market_unavailable') as $error) {
            $this->assertEquals(422, SimulatedPricesController::getHttpStatus($error), $error);
        }

        $this->assertEquals(502, SimulatedPricesController::getHttpStatus('packlink_error'));
        $this->assertEquals(502, SimulatedPricesController::getHttpStatus('unknown'));
    }

    /**
     * @param array|null $origin
     *
     * @return TestShopOriginProvider
     */
    private function registerOriginProvider($origin)
    {
        $provider = new TestShopOriginProvider();
        $provider->origin = $origin;
        TestServiceRegister::registerService(
            ShopOriginProvider::CLASS_NAME,
            function () use ($provider) {
                return $provider;
            }
        );

        return $provider;
    }

    /**
     * @param string $platformCode
     */
    private function setPlatformCode($platformCode)
    {
        $configuration = new BrandConfiguration();
        $configuration->platformCode = $platformCode;
        $brand = $this->getMockBuilder(BrandConfigurationService::CLASS_NAME)->getMock();
        $brand->method('get')->willReturn($configuration);
        TestServiceRegister::registerService(
            BrandConfigurationService::CLASS_NAME,
            function () use ($brand) {
                return $brand;
            }
        );
    }

    /**
     * @return array
     */
    private function getLastQuery()
    {
        parse_str(parse_url($this->httpClient->getLastRequest()['url'], PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * @return SimulatedPricesController
     */
    private function getController()
    {
        return new SimulatedPricesController();
    }

    /**
     * @param string $country
     */
    private function setMerchantCountry($country)
    {
        $user = new User();
        $user->country = $country;
        $this->shopConfig->setUserInfo($user);
    }

    /**
     * @param string $plan
     *
     * @return HttpResponse
     */
    private function subscriptionResponse($plan)
    {
        return new HttpResponse(
            200,
            array(),
            json_encode(array('id' => 'sub_1', 'plan' => array('id' => 'p', 'code' => 'c', 'name' => $plan)))
        );
    }
}
