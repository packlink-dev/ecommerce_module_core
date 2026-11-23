<?php

namespace Logeecom\Tests\BusinessLogic\Http;

use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\ServiceRegister;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Packlink\BusinessLogic\Http\DTO\Package;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\Exceptions\PacklinkErrorException;
use Packlink\BusinessLogic\Http\Proxy;

/**
 * Class ProxyWithoutCredentialsTest. Covers the Packlink calls that must never carry credentials:
 * the unregistered-user pricing and the postal-code search for merchants without an account.
 *
 * @package Logeecom\Tests\BusinessLogic\Http
 */
class ProxyWithoutCredentialsTest extends BaseTestWithServices
{
    public function testSimulatedServicesRequestHasNoCredentials()
    {
        $this->shopConfig->setAuthorizationToken('stale-token');
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));

        $this->getProxy()->getSimulatedShippingServices($this->getSearch(), 'PRO', 'ES');

        $request = $this->httpClient->getLastRequest();
        $this->assertEquals('GET', $request['method']);
        $this->assertNoCredentialHeaders($request['headers']);
        $this->assertEquals('', $request['body']);
    }

    public function testSimulatedServicesRequestUrl()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));
        $search = $this->getSearch();
        $search->priceSimulation = ShippingServiceSearch::PRICE_SIMULATION_ALL;

        $this->getProxy()->getSimulatedShippingServices($search, 'PRO', 'ES');

        $url = $this->httpClient->getLastRequest()['url'];
        $this->assertStringStartsWith(
            'https://api.packlink.com/v1/services/unregistered-user-pricing?',
            $url
        );
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $this->assertEquals('PRO', $query['platform']);
        $this->assertEquals('ES', $query['platform_country']);
        $this->assertEquals('00118', $query['to']['zip']);
        $this->assertEquals('0.5', $query['packages'][0]['weight']);
        $this->assertArrayNotHasKey('price_simulation', $query);
    }

    public function testSimulatedServicesNeverRegistersIntegration()
    {
        $this->shopConfig->setAuthorizationToken('');
        $this->shopConfig->setIntegrationId(null);
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), '[]')));

        $result = $this->getProxy()->getSimulatedShippingServices($this->getSearch(), 'PRO', 'ES');

        $this->assertSame(array(), $result);
        $this->assertCount(1, $this->httpClient->getHistory());
        $this->assertEquals('', $this->shopConfig->getAuthorizationToken());
        $this->assertNull($this->shopConfig->getIntegrationId());
    }

    public function testSimulatedServicesParsesPlanPrices()
    {
        $body = json_encode(
            array(
                array(
                    'id' => 20945,
                    'name' => 'Standard',
                    'carrier_name' => 'UPS',
                    'price' => null,
                    'price_simulation' => array(
                        'current_plan_code' => 'free_es',
                        'prices_per_plan' => array(
                            array('plan_code' => 'plus_es', 'price' => array('total_price' => 3.9, 'currency' => 'EUR')),
                        ),
                    ),
                ),
            )
        );
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $result = $this->getProxy()->getSimulatedShippingServices($this->getSearch(), 'PRO', 'ES');

        $this->assertCount(1, $result);
        $this->assertNull($result[0]->totalPrice);
        $this->assertEquals('plus_es', $result[0]->pricesPerPlan[0]->planCode);
        $this->assertEquals('ES', $result[0]->departureCountry);
        $this->assertEquals('IT', $result[0]->destinationCountry);
        $this->assertFalse($result[0]->national);
    }

    public function testSimulatedServicesErrorCarriesStatusAndErrorCode()
    {
        $body = '{"messages":[{"message":"Not available","error_code":"UNREGISTERED_USER_PRICES_NOT_AVAILABLE"}]}';
        $this->httpClient->setMockResponses(array(new HttpResponse(404, array(), $body)));

        try {
            $this->getProxy()->getSimulatedShippingServices($this->getSearch(), 'PRO', 'XX');
            $this->fail('An exception was expected.');
        } catch (PacklinkErrorException $e) {
            $this->assertEquals(404, $e->getCode());
            $this->assertEquals('UNREGISTERED_USER_PRICES_NOT_AVAILABLE', $e->getErrorCode());
        }
    }

    public function testSimulatedServicesUncodedErrorHasEmptyErrorCode()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(400, array(), '{"messages":[{"message":"Bad Request"}]}')));

        try {
            $this->getProxy()->getSimulatedShippingServices($this->getSearch(), 'PRO', 'ES');
            $this->fail('An exception was expected.');
        } catch (PacklinkErrorException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertEquals('', $e->getErrorCode());
        }
    }

    public function testSimulatedServicesErrorWithoutBody()
    {
        $this->httpClient->setMockResponses(array(new HttpResponse(500, array(), '')));

        try {
            $this->getProxy()->getSimulatedShippingServices($this->getSearch(), 'PRO', 'ES');
            $this->fail('An exception was expected.');
        } catch (PacklinkErrorException $e) {
            $this->assertEquals(500, $e->getCode());
            $this->assertEquals('', $e->getErrorCode());
        }
    }

    public function testSimulatedServicesInvalidSearchFailsBeforeRequest()
    {
        $search = $this->getSearch();
        $search->toZip = '';

        try {
            $this->getProxy()->getSimulatedShippingServices($search, 'PRO', 'ES');
            $this->fail('An exception was expected.');
        } catch (HttpRequestException $e) {
            $this->assertEquals(400, $e->getCode());
        }

        $this->assertEmpty($this->httpClient->getHistory());
    }

    public function testPostalZonesWithoutCredentials()
    {
        $body = '[{"id":"65","name":"Spain - Mainland","isoCode":"ES","phonePrefix":"+34","hasPostalCodes":true},'
            . '{"id":"70","name":"Andorra","isoCode":"AD","phonePrefix":"+376","hasPostalCodes":true}]';
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $zones = $this->getProxy()->getPostalZonesWithoutCredentials('ES');

        $request = $this->httpClient->getLastRequest();
        $this->assertNoCredentialHeaders($request['headers']);
        $this->assertTrue(strpos($request['url'], 'locations/postalzones/destinations?') !== false);
        $this->assertCount(1, $zones);
    }

    public function testSearchLocationsWithoutCredentials()
    {
        $body = '[{"city":"Madrid","postal_zone_id":65,"text":"28001 - Madrid","zipcode":"28001",'
            . '"state":"Espana","id":"abc"}]';
        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $body)));

        $locations = $this->getProxy()->searchLocationsWithoutCredentials('ES', '65', '28001');

        $request = $this->httpClient->getLastRequest();
        $this->assertNoCredentialHeaders($request['headers']);
        $this->assertTrue(strpos($request['url'], 'locations/postalcodes?') !== false);
        $this->assertTrue(strpos($request['url'], 'q=28001') !== false);
        $this->assertCount(1, $locations);
    }

    /**
     * @param array $headers
     */
    private function assertNoCredentialHeaders(array $headers)
    {
        foreach ($headers as $header) {
            $name = strtolower(trim(substr($header, 0, strpos($header, ':'))));
            $this->assertNotEquals('authorization', $name);
            $this->assertNotContains($name, array('x-request-context', 'x-packlink-client-id', 'x-packlink-tenant-id'));
        }
    }

    /**
     * @return ShippingServiceSearch
     */
    private function getSearch()
    {
        $package = new Package('0.5', 10, 3, 15);

        return new ShippingServiceSearch(null, 'ES', '28001', 'IT', '00118', array($package));
    }

    /**
     * @return Proxy
     */
    private function getProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}
