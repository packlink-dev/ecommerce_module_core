<?php

namespace Logeecom\Tests\BusinessLogic\DropOff;

use Logeecom\Infrastructure\Configuration\Configuration;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\Infrastructure\Common\BaseTestWithServices;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\Proxy;

class DropOffDtoTest extends BaseTestWithServices
{
    /**
     * @var TestHttpClient
     */
    public $httpClient;

    protected function setUp()
    {
        parent::setUp();

        $this->httpClient = new TestHttpClient();
        $self = $this;

        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($self) {
                return $self->httpClient;
            }
        );

        TestServiceRegister::registerService(
            Proxy::CLASS_NAME,
            function () use ($self) {
                $config = TestServiceRegister::getService(Configuration::CLASS_NAME);

                return new Proxy($config->getAuthorizationToken(), $self->httpClient);
            }
        );
    }

    /**
     * Tests retrieving DropOff.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function testRetrievingDropOffDto()
    {
        $this->httpClient->setMockResponses($this->getSuccessfulResponses());
        /** @var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        /** @noinspection PhpUnhandledExceptionInspection */
        $dropOffs = $proxy->getLocations('20615', 'FR', '75008');

        $this->assertCount(1, $dropOffs);

        $asArray = $dropOffs[0]->toArray();

        $this->assertEquals('164047', $asArray['id']);
        $this->assertEquals('PARIS BANGLA INTERTIONAL', $asArray['name']);
        $this->assertEquals('', $asArray['type']);
        $this->assertEquals('FR', $asArray['countryCode']);
        $this->assertEquals('', $asArray['state']);
        $this->assertEquals('PARIS', $asArray['city']);
        $this->assertEquals('86. RUE DE LA CONDAMINE', $asArray['address']);
        $this->assertEquals(48.88465881, $asArray['lat']);
        $this->assertEquals( 2.319819927, $asArray['long']);
        $this->assertEquals('', $asArray['phone']);

        $this->assertCount(5, $asArray['workingHours']);

        $this->assertEquals('11:00-14:00, 16:00-19:00', $asArray['workingHours']['saturday']);
    }

    /**
     * Retrieves successful response.
     *
     * @return HttpResponse[]
     */
    protected function getSuccessfulResponses()
    {
        $response = file_get_contents(__DIR__ . '/../Common/ApiResponses/dropOffs.json');

        return array(new HttpResponse(200, array(), $response));
    }
}