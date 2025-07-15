<?php

namespace Logeecom\Tests\BusinessLogic\OAuth;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\ORM\Exceptions\EntityClassException;
use Logeecom\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Logeecom\Infrastructure\ORM\QueryFilter\QueryFilter;
use Logeecom\Infrastructure\ORM\RepositoryRegistry;
use Logeecom\Tests\BusinessLogic\Common\BaseTestWithServices;
use Logeecom\Tests\BusinessLogic\Common\TestComponents\OAuth\OAuthConfigurationService;
use Logeecom\Tests\Infrastructure\Common\TestComponents\ORM\MemoryRepository;
use Logeecom\Tests\Infrastructure\Common\TestComponents\TestHttpClient;
use Logeecom\Tests\Infrastructure\Common\TestServiceRegister;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\OAuth\Models\OAuthInfo;
use Packlink\BusinessLogic\OAuth\Models\OAuthState;
use Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy;
use Packlink\BusinessLogic\OAuth\Services\OAuthService;
use Packlink\BusinessLogic\OAuth\Services\OAuthStateService;
use Packlink\BusinessLogic\OAuth\Services\TenantDomainProvider;

class OAuthServiceTest extends BaseTestWithServices
{
    /**
     * OAuth service instance.
     *
     * @var OAuthService
     */
    public $service;

    /**
     * @var MemoryRepository
     */
    public $stateRepository;

    /**
     * @var MemoryRepository
     */
    public $repository;

    /**
     * @var HttpClient
     */
    public $httpClientOauth;

    /**
     * @var OAuthStateService
     */
    public $stateService;

    /**
     * @before
     * @inheritdoc
     */
    protected function before()
    {
        parent::before();

        $this->httpClientOauth = new TestHttpClient();

        $mockResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'mockAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'mockRefreshToken',
            )
        ));
        $this->httpClientOauth->setMockResponses(array($mockResponse));

        $oAuth = new OAuthConfigurationService();

        $oAuth->setDomain('tenant1');
        $oAuth->setClientId('client');
        $oAuth->setClientSecret('client_secret');
        $oAuth->setRedirectUri('www.example.com');
        $oAuth->setTenantId('tenant1');
        $oAuth->setScopes(array('write','read'));


        $authProxy = new OAuthProxy($oAuth, $this->httpClientOauth);

        RepositoryRegistry::registerRepository(OAuthInfo::CLASS_NAME, MemoryRepository::getClassName());

        $this->repository = RepositoryRegistry::getRepository(OAuthInfo::CLASS_NAME);

        /**@var Proxy $proxy */
        $proxy = TestServiceRegister::getService(Proxy::CLASS_NAME);

        RepositoryRegistry::registerRepository(OAuthState::CLASS_NAME, MemoryRepository::getClassName());

        $this->stateRepository = RepositoryRegistry::getRepository(OAuthState::CLASS_NAME);

        $this->stateService = new OAuthStateService($this->stateRepository);

        $this->service = new OAuthService($authProxy, $proxy, $this->repository, $this->stateService, $oAuth);
    }

    /**
     * @throws QueryFilterInvalidParamException|EntityClassException
     */
    public function testBuildRedirectUrl()
    {
        $actualUrl = $this->service->buildRedirectUrlAndSaveState('ES');

        $filter = new QueryFilter();
        $filter->where('tenantId', '=', 'tenant1');
        /**@var OAuthState $state*/
        $state = $this->stateRepository->selectOne($filter);

        $expectedParams = http_build_query(array(
            'response_type' => 'code',
            'client_id' => 'client',
            'redirect_uri' => 'www.example.com',
            'scope' => 'write read',
            'state' =>  $state->getState(),
        ), '', '&', PHP_QUERY_RFC3986);

        $expectedUrl = 'https://' . TenantDomainProvider::getDomain('ES') . '/auth/oauth2/authorize?' . $expectedParams;

        $this->assertEquals($expectedUrl, $actualUrl);
    }

    /**
     * @throws HttpCommunicationException
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     * @throws EntityClassException
     */
    public function testConnectStoresTokenAndReturnsApiKey()
    {
        $state = $this->stateService->generateAndSaveState('tenant1');
        $connectData = new \Packlink\BusinessLogic\Http\DTO\OAuthConnectData('code123',$state);

        $mockResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'token' => 'apiKey',
            )
        ));

        $this->httpClient->setMockResponses(array($mockResponse));

        $apiKey = $this->service->connect($connectData);

        $this->assertEquals('apiKey', $apiKey);

        $entities = $this->repository->select();
        $this->assertCount(0, $entities);
    }

    public function testConnectRefreshesTokenOnAuthFailure()
    {
        $state = $this->stateService->generateAndSaveState('tenant1');
        $connectData = new \Packlink\BusinessLogic\Http\DTO\OAuthConnectData('codeXYZ', $state);

        $invalidResponse = new \Logeecom\Infrastructure\Http\HttpResponse(201, array(), json_encode(array(
            'unexpected_field' => 'notToken',
        )));

        $validResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'token' => 'apiKeyAfterRefresh',
            )
        ));

        $this->httpClient->setMockResponses(array($invalidResponse, $validResponse));

        $mockResponse1 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'mockAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 0,
                'refresh_token' => 'mockRefreshToken',
            )
        ));

        $mockResponse2 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'newAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'newRefreshToken',
            )
        ));
        $this->httpClientOauth->setMockResponses(array($mockResponse1,$mockResponse2));

        $apiKey = $this->service->connect($connectData);

        $this->assertEquals('apiKeyAfterRefresh', $apiKey);

        $entities = $this->repository->select();
        $this->assertCount(1, $entities);

        $entity = $entities[0];
        $this->assertEquals('tenant1', $entity->getTenantId());
        $this->assertEquals('newAccessToken', $entity->getAccessToken());
        $this->assertEquals('newRefreshToken', $entity->getRefreshToken());
    }

    /**
     * @return void
     *
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function testConnectOnAuthFailureTokenNotExpired()
    {
        $state = $this->stateService->generateAndSaveState('tenant1');
        $connectData = new \Packlink\BusinessLogic\Http\DTO\OAuthConnectData('codeXYZ', $state);

        $invalidResponse = new \Logeecom\Infrastructure\Http\HttpResponse(201, array(), json_encode(array(
            'unexpected_field' => 'notToken',
        )));

        $validResponse = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'token' => 'apiKeyAfterRefresh',
            )
        ));

        $this->httpClient->setMockResponses(array($invalidResponse, $validResponse));

        $mockResponse1 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'mockAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'mockRefreshToken',
            )
        ));

        $mockResponse2 = new \Logeecom\Infrastructure\Http\HttpResponse(200, array(), json_encode(array(
                'access_token' => 'newAccessToken',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token' => 'newRefreshToken',
            )
        ));
        $this->httpClientOauth->setMockResponses(array($mockResponse1,$mockResponse2));

        try {
            $this->service->connect($connectData);
            $this->fail('Expected HttpAuthenticationException was not thrown.');
        } catch (HttpAuthenticationException $e) {
            $this->assertEquals('Could not retrieve API key.', $e->getMessage());
        }
    }
}