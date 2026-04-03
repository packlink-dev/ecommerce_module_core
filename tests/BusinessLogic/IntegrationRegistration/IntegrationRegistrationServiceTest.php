<?php

namespace Logeecom\Tests\BusinessLogic\IntegrationRegistration;

use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\IntegrationRegistration\MockIntegrationRegistrationDataProvider;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\IntegrationRegistration\IntegrationRegistrationService;

class IntegrationRegistrationServiceTest extends BaseTestWithServices
{
    /**
     * @var IntegrationRegistrationService
     */
    protected $integrationRegistrationService;
    /**
     * @var Proxy
     */
    protected $proxy;
    /**
     * @var MockIntegrationRegistrationDataProvider
     */
    protected $dataProvider;
    /**
     * @var Configuration
     */
    protected $configService;

    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        $me = $this;

        $this->dataProvider = new MockIntegrationRegistrationDataProvider();

        /** @var Configuration $config */
        $this->configService = TestServiceRegister::getService(Configuration::CLASS_NAME);
        // Clear the integration ID set by the base class so tests start clean
        $this->configService->setIntegrationId(null);

        $me->proxy = new Proxy($this->configService, $this->httpClient, $this->dataProvider);

        $me->integrationRegistrationService = new IntegrationRegistrationService(
            $me->proxy,
            $me->dataProvider,
            $this->configService
        );
    }

    /**
     * @after
     * @inheritdoc
     */
    protected function after()
    {
        parent::after();
    }

    /**
     * If an integration ID already exists, registerIntegration() should return
     * it immediately without making any API call.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function testRegisterIntegrationReturnsExistingId()
    {
        $this->configService->setIntegrationId('existing-id-123');

        $result = $this->integrationRegistrationService->registerIntegration();

        $this->assertEquals('existing-id-123', $result);
        $this->assertEmpty($this->httpClient->getHistory());
    }

    /**
     *  When no integration ID is stored, registerIntegration() should call the
     *  proxy, persist the returned ID via the data provider, and return it.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function testRegisterIntegrationCallsProxyWhenNoIdStored()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockRegisterIntegrationResponse('new-integration-id'),
        ));

        $result = $this->integrationRegistrationService->registerIntegration();

        $this->assertEquals('new-integration-id', $result);
    }

    /**
     * After a successful registration the ID must be persisted in configuration.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function testRegisterIntegrationPersistsId()
    {
        $this->httpClient->setMockResponses(array(
            $this->getMockRegisterIntegrationResponse('persisted-id'),
        ));

        $this->integrationRegistrationService->registerIntegration();

        $this->assertEquals('persisted-id', $this->configService->getIntegrationId());
    }

    /**
     * When no integration ID is stored (legacy merchant), disconnectIntegration()
     * should return void/null without making any API call.
     *
     * @return void
     */
    public function testDisconnectIntegrationSkipsWhenNoId()
    {
        $result = $this->integrationRegistrationService->disconnectIntegration();

        $this->assertNull($result);
        $this->assertEmpty($this->httpClient->getHistory());
    }

    /**
     * A successful API disconnect should return true.
     *
     * @return void
     */
    public function testDisconnectIntegrationReturnsTrueOnSuccess()
    {
        $this->configService->setIntegrationId('some-id');
        $this->httpClient->setMockResponses(array(
            $this->getMockDisconnectIntegrationResponse(true),
        ));

        $result = $this->integrationRegistrationService->disconnectIntegration();

        $this->assertTrue($result);
    }

    /**
     * When the API returns false (failure signal), disconnectIntegration()
     * should return false and log an error.
     */
    public function testDisconnectIntegrationReturnsFalseWhenApiFails()
    {
        $this->configService->setIntegrationId('some-id');
        $this->httpClient->setMockResponses(array(
            $this->getMockDisconnectIntegrationResponse(false),
        ));

        $result = $this->integrationRegistrationService->disconnectIntegration();

        $this->assertFalse($result);
    }

    /**
     * When the proxy throws an exception, disconnectIntegration() should catch
     * it, log the error, and return false — not propagate the exception.
     */
    public function testDisconnectIntegrationReturnsFalseOnException()
    {
        $this->configService->setIntegrationId('some-id');
        $this->httpClient->setMockResponses(array(
            $this->getMockErrorResponse(),
        ));

        $result = $this->integrationRegistrationService->disconnectIntegration();

        $this->assertFalse($result);
    }

    /**
     * getIntegrationId() should delegate to configuration and return whatever
     * ID is currently stored.
     */
    public function testGetIntegrationIdReturnsStoredId()
    {
        $this->configService->setIntegrationId('delegate-id');

        $result = $this->integrationRegistrationService->getIntegrationId();

        $this->assertEquals('delegate-id', $result);
    }

    /**
     * getIntegrationId() should return null when no ID has been persisted.
     */
    public function testGetIntegrationIdReturnsNullWhenNotSet()
    {
        $result = $this->integrationRegistrationService->getIntegrationId();

        $this->assertNull($result);
    }

    /**
     * updateIntegrationUrl() should disconnect, wipe the stored data, re-register,
     *  and return the newly assigned integration ID.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function testUpdateIntegrationUrlReturnsNewId()
    {
        $this->configService->setIntegrationId('old-id');
        $this->httpClient->setMockResponses(array(
            $this->getMockDisconnectIntegrationResponse(true),
            $this->getMockRegisterIntegrationResponse('new-id-after-update'),
        ));

        $result = $this->integrationRegistrationService->updateIntegrationUrl();

        $this->assertEquals('new-id-after-update', $result);
    }

    /**
     * When disconnect fails, updateIntegrationUrl() must abort and return null
     *  without attempting to re-register.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function testUpdateIntegrationUrlReturnsNullWhenDisconnectFails()
    {
        $this->configService->setIntegrationId('old-id');
        $this->httpClient->setMockResponses(array(
            $this->getMockDisconnectIntegrationResponse(false),
        ));

        $result = $this->integrationRegistrationService->updateIntegrationUrl();

        $this->assertNull($result);
    }

    /**
     * After a successful updateIntegrationUrl() call the configuration must hold
     *  the new ID, not the old one.
     *
     * @return void
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\IntegrationRegistration\Exceptions\IntegrationNotRegisteredException
     */
    public function testUpdateIntegrationUrlPersistsNewId()
    {
        $this->configService->setIntegrationId('old-id');
        $this->httpClient->setMockResponses(array(
            $this->getMockDisconnectIntegrationResponse(true),
            $this->getMockRegisterIntegrationResponse('brand-new-id'),
        ));

        $this->integrationRegistrationService->updateIntegrationUrl();

        $this->assertEquals('brand-new-id', $this->configService->getIntegrationId());
    }

    /**
     * Returns a mock HTTP 200 response whose body contains the integration ID
     * in the format the Proxy::registerIntegration() method expects.
     *
     * @param string $integrationId
     * @return \Logeecom\Infrastructure\Http\HttpResponse
     */
    private function getMockRegisterIntegrationResponse($integrationId)
    {
        return new \Logeecom\Infrastructure\Http\HttpResponse(
            200,
            array(),
            json_encode(array('integration_id' => $integrationId))
        );
    }

    /**
     * Returns a mock HTTP response for the disconnect endpoint.
     * A 200 with success=true signals success; success=false signals failure.
     *
     * @param bool $success
     * @return \Logeecom\Infrastructure\Http\HttpResponse
     */
    private function getMockDisconnectIntegrationResponse($success)
    {
        $statusCode = $success ? 204 : 400;

        return new \Logeecom\Infrastructure\Http\HttpResponse(
            $statusCode,
            array(),
            ''
        );
    }

    /**
     * Returns a mock HTTP 500 response to trigger exception handling paths.
     *
     * @return \Logeecom\Infrastructure\Http\HttpResponse
     */
    private function getMockErrorResponse()
    {
        return new \Logeecom\Infrastructure\Http\HttpResponse(
            500,
            array(),
            json_encode(array('message' => 'Internal Server Error'))
        );
    }
}
