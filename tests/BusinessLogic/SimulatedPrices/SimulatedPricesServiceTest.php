<?php

namespace Logeecom\Tests\BusinessLogic\SimulatedPrices;

use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\Dto\TestWarehouse;
use Logeecom\Tests\BusinessLogic\ShippingMethod\TestShopShippingMethodService;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\ShippingMethod\Interfaces\ShopShippingMethodService;
use Packlink\BusinessLogic\SimulatedPrices\DTO\SimulatedPricesSearch;
use Packlink\BusinessLogic\SimulatedPrices\SimulatedPricesService;
use Packlink\BusinessLogic\Subscription\SubscriptionService;

/**
 * Class SimulatedPricesServiceTest.
 *
 * @package Logeecom\Tests\BusinessLogic\SimulatedPrices
 */
class SimulatedPricesServiceTest extends BaseTestWithServices
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

    public function testNonConnectedDefaultsToCapitalCityAndFixedParcelWithoutCredentials()
    {
        $this->disconnect();
        $this->mockResponses(array($this->servicesResponse($this->threeServices())));

        $result = $this->search(array('market' => 'ES'));

        $history = $this->httpClient->getHistory();
        $this->assertCount(1, $history);
        $query = $this->getQuery($history[0]['url']);
        $this->assertTrue(strpos($history[0]['url'], 'services/unregistered-user-pricing') !== false);
        $this->assertEquals('ES', $query['platform_country']);
        $this->assertEquals(array('country' => 'ES', 'zip' => '28001'), $query['from']);
        $this->assertEquals(array('country' => 'ES', 'zip' => '28001'), $query['to']);
        $this->assertEquals(
            array('height' => '3', 'width' => '10', 'length' => '15', 'weight' => '0.5'),
            $query['packages'][0]
        );
        foreach ($history[0]['headers'] as $header) {
            $this->assertStringStartsNotWith('Authorization', $header);
        }

        $this->assertNull($result['error']);
        $this->assertFalse($result['connected']);
        $this->assertNull($result['currentTier']);
        $this->assertEquals(array('FREE', 'PLUS', 'PREMIUM'), $result['tiers']);
    }

    public function testNonConnectedListsAllServicesSortedByFreePriceWithoutCurrentPlan()
    {
        $this->disconnect();
        $this->mockResponses(array($this->servicesResponse($this->threeServices())));

        $result = $this->search(array('market' => 'ES'));

        $this->assertEquals(array('SEUR', 'DHL', 'Correos'), $this->carrierNames($result));
        $first = $result['services'][0];
        $this->assertEquals(2, $first['id']);
        $this->assertEquals('SEUR 24', $first['serviceName']);
        $this->assertEquals('tmp://SEUR', $first['logoUrl']);
        $this->assertEquals('1 DAYS', $first['transitTime']);
        $this->assertEquals(
            array(
                array('tier' => 'FREE', 'totalPrice' => 4.1, 'currency' => 'EUR', 'isCurrent' => false),
                array('tier' => 'PLUS', 'totalPrice' => 3.9, 'currency' => 'EUR', 'isCurrent' => false),
                array('tier' => 'PREMIUM', 'totalPrice' => 3.7, 'currency' => 'EUR', 'isCurrent' => false),
            ),
            $first['prices']
        );
        $this->assertEquals(array('tier' => 'FREE', 'totalPrice' => 4.1, 'currency' => 'EUR'), $result['fromPrice']);
    }

    public function testNonConnectedUsesModuleOriginInsideTheMarket()
    {
        $this->disconnect();
        $this->mockResponses(array($this->servicesResponse($this->threeServices())));

        $this->search(array('market' => 'ES', 'fromCountry' => 'ES', 'fromPostalCode' => '08001'));

        $query = $this->getQuery($this->httpClient->getLastRequest()['url']);
        $this->assertEquals('ES', $query['platform_country']);
        $this->assertEquals(array('country' => 'ES', 'zip' => '08001'), $query['from']);
    }

    public function testNonConnectedModuleOriginOutsideTheMarketIsReplacedByItsCapital()
    {
        $this->disconnect();
        $this->mockResponses(array($this->servicesResponse($this->threeServices())));

        $this->search(array('market' => 'IT', 'fromCountry' => 'ES', 'fromPostalCode' => '08001'));

        $query = $this->getQuery($this->httpClient->getLastRequest()['url']);
        $this->assertEquals('IT', $query['platform_country']);
        $this->assertEquals(array('country' => 'IT', 'zip' => '00118'), $query['from']);
        $this->assertEquals(array('country' => 'IT', 'zip' => '00118'), $query['to']);
    }

    public function testNonConnectedUkChipIsQuotedAndMapsPlatformRequiredToMarketUnavailable()
    {
        $this->disconnect();
        $this->mockResponses(
            array(
                new HttpResponse(
                    400,
                    array(),
                    '{"messages":[{"message":"Platform required","error_code":"PLATFORM_REQUIRED"}]}'
                ),
            )
        );

        $result = $this->search(array('market' => 'GB'));

        $query = $this->getQuery($this->httpClient->getLastRequest()['url']);
        $this->assertEquals('GB', $query['platform_country']);
        $this->assertEquals(array('country' => 'GB', 'zip' => 'SW1A 1AA'), $query['to']);
        $this->assertEquals('market_unavailable', $result['error']);
        $this->assertSame(array(), $result['services']);
    }

    public function testNonConnectedErrorMapping()
    {
        $cases = array(
            array(404, '{"messages":[{"message":"x","error_code":"UNREGISTERED_USER_PRICES_NOT_AVAILABLE"}]}', 'market_unavailable'),
            array(400, '{"messages":[{"message":"Bad Request"}]}', 'validation'),
            array(400, '{"messages":[{"message":"x","error_code":"CLIENT_REQUIRED"}]}', 'packlink_error'),
            array(401, '{"message":"Unauthorized"}', 'packlink_error'),
            array(403, '', 'packlink_error'),
            array(500, '{"messages":[{"message":"x","error_code":"INTERNAL_ERROR"}]}', 'packlink_error'),
            array(500, '', 'packlink_error'),
        );

        $this->disconnect();
        foreach ($cases as $case) {
            $this->mockResponses(array(new HttpResponse($case[0], array(), $case[1])));

            $result = $this->search(array('market' => 'ES'));

            $this->assertEquals($case[2], $result['error'], "HTTP {$case[0]} {$case[1]}");
        }
    }

    public function testNonConnectedEmptyResultIsNoServices()
    {
        $this->disconnect();
        $this->mockResponses(array($this->servicesResponse(array())));

        $result = $this->search(array('market' => 'ES'));

        $this->assertEquals('no_services', $result['error']);
        $this->assertNull($result['fromPrice']);
    }

    public function testUnknownMarketIsValidationErrorWithoutRequest()
    {
        $this->disconnect();

        $result = $this->search(array('market' => 'PT'));

        $this->assertEquals('validation', $result['error']);
        $this->assertEmpty($this->httpClient->getHistory());
    }

    public function testDestinationOutsideMarketNeedsPostalCode()
    {
        $this->disconnect();

        $result = $this->search(array('market' => 'ES', 'toCountry' => 'PT'));

        $this->assertEquals('validation', $result['error']);
        $this->assertEmpty($this->httpClient->getHistory());
    }

    public function testMissingPricesAreNeverZero()
    {
        $this->disconnect();
        $services = array(
            $this->service(1, 'A', array('free_es' => null, 'plus_es' => null, 'premium_es' => null)),
            $this->service(2, 'B', array('free_es' => 5.0, 'plus_es' => null, 'unknown_es' => 1.0)),
        );
        $this->mockResponses(array($this->servicesResponse($services)));

        $result = $this->search(array('market' => 'ES'));

        $this->assertEquals(array('B'), $this->carrierNames($result));
        $this->assertEquals(
            array(array('tier' => 'FREE', 'totalPrice' => 5.0, 'currency' => 'EUR', 'isCurrent' => false)),
            $result['services'][0]['prices']
        );
    }

    public function testServiceWithoutFirstColumnPriceGoesLast()
    {
        $this->disconnect();
        $services = array(
            $this->service(1, 'NoFree', array('plus_es' => 1.0)),
            $this->service(2, 'Expensive', array('free_es' => 9.0)),
            $this->service(3, 'TieHighPlus', array('free_es' => 4.0, 'plus_es' => 3.5)),
            $this->service(4, 'TieLowPlus', array('free_es' => 4.0, 'plus_es' => 3.0)),
        );
        $this->mockResponses(array($this->servicesResponse($services)));

        $result = $this->search(array('market' => 'ES'));

        $this->assertEquals(array('TieLowPlus', 'TieHighPlus', 'Expensive', 'NoFree'), $this->carrierNames($result));
    }

    public function testConnectedFreeMerchantSeesAllTiersWithCurrentPlanAndNextPlanNote()
    {
        $this->connect();
        $this->mockResponses(
            array($this->subscriptionResponse('Free'), $this->servicesResponse($this->threeServices()))
        );

        $result = $this->search(array('market' => 'ES', 'toCountry' => 'FR', 'toPostalCode' => '75001'));

        $request = $this->httpClient->getLastRequest();
        $query = $this->getQuery($request['url']);
        $this->assertTrue(strpos($request['url'], '/v1/services?') !== false);
        $this->assertEquals('all', $query['price_simulation']);
        $this->assertEquals(array('country' => 'ES', 'zip' => '28001'), $query['from']);
        $this->assertEquals(array('country' => 'FR', 'zip' => '75001'), $query['to']);
        $this->assertContains('Authorization: mock-auth-token', $request['headers']);

        $this->assertTrue($result['connected']);
        $this->assertEquals('FREE', $result['currentTier']);
        $this->assertEquals(array('FREE', 'PLUS', 'PREMIUM'), $result['tiers']);
        $this->assertTrue($result['services'][0]['prices'][0]['isCurrent']);
        $this->assertFalse($result['services'][0]['prices'][1]['isCurrent']);
        $this->assertEquals(array('tier' => 'PLUS', 'totalPrice' => 3.9, 'currency' => 'EUR'), $result['fromPrice']);
    }

    public function testConnectedPlusMerchantSeesPlusAndPremiumSortedByPlus()
    {
        $this->connect();
        $services = array(
            $this->service(1, 'B', array('free_es' => 4.8, 'plus_es' => 4.5, 'premium_es' => 4.4)),
            $this->service(2, 'A', array('free_es' => 5.0, 'plus_es' => 4.0, 'premium_es' => 3.8)),
        );
        $this->mockResponses(array($this->subscriptionResponse('Plus'), $this->servicesResponse($services)));

        $result = $this->search(array('market' => 'ES'));

        $this->assertEquals('PLUS', $result['currentTier']);
        $this->assertEquals(array('PLUS', 'PREMIUM'), $result['tiers']);
        $this->assertEquals(array('A', 'B'), $this->carrierNames($result));
        $this->assertEquals(
            array(
                array('tier' => 'PLUS', 'totalPrice' => 4.0, 'currency' => 'EUR', 'isCurrent' => true),
                array('tier' => 'PREMIUM', 'totalPrice' => 3.8, 'currency' => 'EUR', 'isCurrent' => false),
            ),
            $result['services'][0]['prices']
        );
        $this->assertEquals(
            array('tier' => 'PREMIUM', 'totalPrice' => 3.8, 'currency' => 'EUR'),
            $result['fromPrice']
        );
    }

    public function testConnectedPremiumMerchantIsRejected()
    {
        $this->connect();
        $this->mockResponses(array($this->subscriptionResponse('Premium')));

        $result = $this->search(array('market' => 'ES'));

        $this->assertEquals('premium', $result['error']);
        $this->assertCount(1, $this->httpClient->getHistory());
    }

    public function testConnectedSubscriptionFailureIsTreatedAsFree()
    {
        $this->connect();
        $this->mockResponses(
            array(new HttpResponse(500, array(), '{"message":"err"}'), $this->servicesResponse($this->threeServices()))
        );

        $result = $this->search(array('market' => 'ES'));

        $this->assertNull($result['error']);
        $this->assertEquals('FREE', $result['currentTier']);
    }

    public function testConnectedMerchantChoosesDestination()
    {
        $this->connect();
        $this->mockResponses(
            array($this->subscriptionResponse('Free'), $this->servicesResponse($this->threeServices()))
        );

        $this->search(array('market' => 'ES', 'toCountry' => 'FR', 'toPostalCode' => '69001'));

        $query = $this->getQuery($this->httpClient->getLastRequest()['url']);
        $this->assertEquals(array('country' => 'FR', 'zip' => '69001'), $query['to']);
    }

    public function testConnectedWithoutWarehouseShipsFromTheCapitalOfTheAccountCountry()
    {
        $this->connect(false);
        $this->mockResponses(
            array($this->subscriptionResponse('Free'), $this->servicesResponse($this->threeServices()))
        );

        $result = $this->search(array('market' => 'ES'));

        $query = $this->getQuery($this->httpClient->getLastRequest()['url']);
        $this->assertNull($result['error']);
        $this->assertEquals(array('country' => 'ES', 'zip' => '28001'), $query['from']);
    }

    public function testConnectedWarehouseOutsideTheAccountCountryIsReplacedByTheCapital()
    {
        $this->connect(true, 'FR');
        $this->mockResponses(
            array($this->subscriptionResponse('Free'), $this->servicesResponse($this->threeServices()))
        );

        $result = $this->search(array('market' => 'FR'));

        $request = $this->httpClient->getLastRequest();
        $query = $this->getQuery($request['url']);
        $this->assertTrue(strpos($request['url'], '/v1/services?') !== false);
        $this->assertEquals(array('country' => 'FR', 'zip' => '75001'), $query['from']);
        $this->assertTrue($result['connected']);
        $this->assertEquals('FREE', $result['currentTier']);
    }

    public function testConnectedMerchantIsAlwaysQuotedOnTheAccountMarket()
    {
        $this->connect(true, 'FR');
        $this->mockResponses(
            array($this->subscriptionResponse('Free'), $this->servicesResponse($this->threeServices()))
        );

        $result = $this->search(array('market' => 'IT', 'toCountry' => 'IT', 'toPostalCode' => '00118'));

        $request = $this->httpClient->getLastRequest();
        $query = $this->getQuery($request['url']);
        $this->assertTrue(strpos($request['url'], '/v1/services?') !== false);
        $this->assertEquals(array('country' => 'FR', 'zip' => '75001'), $query['from']);
        $this->assertEquals(array('country' => 'IT', 'zip' => '00118'), $query['to']);
        $this->assertTrue($result['connected']);
        $this->assertEquals('FR', $result['market']);
        $this->assertEquals('FREE', $result['currentTier']);
    }

    public function testConnectedPremiumMerchantIsRejectedOnEveryMarket()
    {
        $this->connect(true, 'FR');
        $this->mockResponses(array($this->subscriptionResponse('Premium')));

        $result = $this->search(array('market' => 'IT'));

        $this->assertEquals('premium', $result['error']);
        $this->assertCount(1, $this->httpClient->getHistory());
    }

    public function testConnectedPacklinkFailureIsPacklinkError()
    {
        $this->connect();
        $this->mockResponses(
            array($this->subscriptionResponse('Free'), new HttpResponse(500, array(), '{"message":"err"}'))
        );

        $result = $this->search(array('market' => 'ES'));

        $this->assertEquals('packlink_error', $result['error']);
    }

    public function testMarketsAreTheFiveChipsWithCapitalCities()
    {
        $this->assertEquals(
            array(
                array('code' => 'ES', 'postalCode' => '28001', 'city' => 'Madrid'),
                array('code' => 'FR', 'postalCode' => '75001', 'city' => 'Paris'),
                array('code' => 'DE', 'postalCode' => '10115', 'city' => 'Berlin'),
                array('code' => 'IT', 'postalCode' => '00118', 'city' => 'Roma'),
                array('code' => 'GB', 'postalCode' => 'SW1A 1AA', 'city' => 'London'),
            ),
            SimulatedPricesService::getMarkets()
        );
    }

    /**
     * @param array $payload
     *
     * @return array
     */
    private function search(array $payload)
    {
        return SimulatedPricesService::getInstance()->search(SimulatedPricesSearch::fromArray($payload));
    }

    private function disconnect()
    {
        $this->shopConfig->setAuthorizationToken('');
    }

    /**
     * @param bool $withWarehouse
     */
    private function connect($withWarehouse = true, $accountCountry = 'ES')
    {
        $this->shopConfig->setAuthorizationToken('mock-auth-token');
        $user = new User();
        $user->country = $accountCountry;
        $this->shopConfig->setUserInfo($user);
        if ($withWarehouse) {
            $this->shopConfig->setDefaultWarehouse(new TestWarehouse());
        }
    }

    /**
     * @param HttpResponse[] $responses
     */
    private function mockResponses(array $responses)
    {
        $this->httpClient->setMockResponses($responses);
    }

    /**
     * @param string $url
     *
     * @return array
     */
    private function getQuery($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    /**
     * @param array $result
     *
     * @return string[]
     */
    private function carrierNames(array $result)
    {
        $names = array();
        foreach ($result['services'] as $service) {
            $names[] = $service['carrierName'];
        }

        return $names;
    }

    /**
     * @return array
     */
    private function threeServices()
    {
        return array(
            $this->service(1, 'DHL', array('free_es' => 4.6, 'plus_es' => 4.3, 'premium_es' => 4.1)),
            $this->service(2, 'SEUR', array('free_es' => 4.1, 'plus_es' => 3.9, 'premium_es' => 3.7)),
            $this->service(3, 'Correos', array('free_es' => 4.95, 'plus_es' => 4.6, 'premium_es' => 4.4)),
        );
    }

    /**
     * @param int $id
     * @param string $carrier
     * @param array $plans Plan code => total price (or null).
     *
     * @return array
     */
    private function service($id, $carrier, array $plans)
    {
        $pricesPerPlan = array();
        foreach ($plans as $code => $price) {
            $pricesPerPlan[] = array(
                'plan_code' => $code,
                'price' => $price === null ? null : array('total_price' => $price, 'currency' => 'EUR'),
            );
        }

        return array(
            'id' => $id,
            'name' => $carrier . ' 24',
            'carrier_name' => $carrier,
            'transit_time' => '1 DAYS',
            'price' => isset($plans['free_es']) ? array('total_price' => $plans['free_es'], 'currency' => 'EUR') : null,
            'price_simulation' => array(
                'current_plan_code' => 'free_es',
                'next_case_plan_code' => 'plus_es',
                'prices_per_plan' => $pricesPerPlan,
            ),
        );
    }

    /**
     * @param array $services
     *
     * @return HttpResponse
     */
    private function servicesResponse(array $services)
    {
        return new HttpResponse(200, array(), json_encode($services));
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
