<?php

namespace Logeecom\Tests\BusinessLogic\OAuth;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\OAuth\OAuthConfigurationService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy;

class OAuthProxyTest extends BaseTestWithServices
{
    protected function setUp()
    {
        $this->before();
    }
    /** @var OAuthProxy */
    private $proxy;

    protected function before()
    {
        parent::before();

        $me = $this;
        $this->httpClient = new TestHttpClient();
        TestServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () use ($me) {
                return $me->httpClient;
            }
        );

        $oAuth = new OAuthConfigurationService();

        $oAuth->setDomain('tenant1');
        $oAuth->setClientId('client');
        $oAuth->setClientSecret('client_secret');
        $oAuth->setRedirectUri('www.example.com');
        $oAuth->setTenantId('tenant1');
        $oAuth->setScopes(array('write','read'));


        $this->proxy = new OAuthProxy($oAuth, $this->httpClient);
    }

    /**
     * @return void
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testGetAuthTokenReturnsValidToken()
    {
        $response = json_encode(array(
            'access_token' => 'access123',
            'token_type' => 'bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh123',
        ));

        $this->httpClient->setMockResponses(array(new HttpResponse(200, array(), $response)));

        $token = $this->proxy->getAuthToken('auth-code-123');

        $this->assertEquals('access123', $token->getAccessToken());
        $this->assertEquals('bearer', $token->getTokenType());
        $this->assertEquals(3600, $token->getExpiresIn());
        $this->assertEquals('refresh123', $token->getRefreshToken());
    }

    /**
     * @return void
     *
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testRefreshAuthTokenReturnsToken()
    {
        $mockResponse = new HttpResponse(200, array(), json_encode(array(
            'access_token' => 'new-token',
            'token_type' => 'Bearer',
            'expires_in' => 1800,
            'refresh_token' => 'new-refresh',
        )));

        $this->httpClient->setMockResponses(array($mockResponse));

        $token = $this->proxy->refreshAuthToken('old-refresh-token');

        $this->assertEquals('new-token', $token->getAccessToken());
    }

    /**
     * @return void
     *
     * @throws HttpRequestException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testRequestTokenThrowsAuthenticationException()
    {
        $mockResponse = new HttpResponse(401, array(), json_encode(array(
            'error' => 'invalid_client',
            'error_description' => 'Client authentication failed',
        )));

        $this->httpClient->setMockResponses(array($mockResponse));

        try {
            $this->proxy->getAuthToken('invalid-code');
            $this->fail('Expected HttpAuthenticationException was not thrown.');
        } catch (HttpAuthenticationException $e) {
            $this->assertEquals('Client authentication failed', $e->getMessage());
        }
    }

    /**
     * @return void
     *
     * @throws HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
     */
    public function testRequestTokenThrowsRequestException()
    {
        $mockResponse = new HttpResponse(400, array(), json_encode(array(
            'error' => 'invalid_request',
            'error_description' => 'Missing parameters',
        )));

        $this->httpClient->setMockResponses(array($mockResponse));

        try {
            $this->proxy->getAuthToken('');
            $this->fail('Expected HttpRequestException was not thrown.');
        } catch (HttpRequestException $e) {
            $this->assertEquals('Missing parameters', $e->getMessage());
        }
    }

    /**
     * @return void
     *
     * @throws \ReflectionException
     */
    public function testGetRequestHeaders()
    {
        $reflection = new \ReflectionClass('Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy');
        $method = $reflection->getMethod('getRequestHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->proxy);

        $this->assertTrue(array_key_exists('Authorization', $headers));
        $this->assertContains('Basic', $headers['Authorization']);
        $this->assertTrue(array_key_exists('Content-Type', $headers));
        $this->assertEquals('Content-Type: application/x-www-form-urlencoded', $headers['Content-Type']);
    }
}