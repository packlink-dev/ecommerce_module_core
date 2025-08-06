<?php

namespace Packlink\BusinessLogic\OAuth\Services;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Http\DTO\OAuthConnectData;
use Packlink\BusinessLogic\Http\DTO\OAuthUrlData;
use Packlink\BusinessLogic\Http\Proxy;
use Packlink\BusinessLogic\OAuth\Models\OAuthInfo;
use Packlink\BusinessLogic\OAuth\Proxy\Interfaces\OAuthProxyInterface;
use Packlink\BusinessLogic\OAuth\Proxy\OAuthProxy;
use Packlink\BusinessLogic\OAuth\Services\Interfaces\OAuthServiceInterface;
use Packlink\BusinessLogic\OAuth\Services\Interfaces\OAuthStateServiceInterface;

class OAuthService implements OAuthServiceInterface
{
    /** @var OAuthProxy */
    protected $proxy;

    /**
     * @var RepositoryInterface
     */
    protected $repository;
    /**
     * @var OAuthStateServiceInterface
     */
    protected $stateService;


    public function __construct(OAuthProxyInterface $proxy, RepositoryInterface $repository, OAuthStateServiceInterface $stateService)
    {
        $this->proxy = $proxy;
        $this->repository = $repository;
        $this->stateService = $stateService;
    }

    /**
     *  Connects the user by exchanging the authorization code for an access token,retrieves the API key using the access token, and handles token refresh if needed.
     * @param OAuthConnectData $data
     *
     * @return string
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function connect(OAuthConnectData $data)
    {
        $this->stateService->validateState($data->getState());
        $token = $this->getToken($data->getAuthorizationCode());

        $entity = new OAuthInfo();
        $entity->setTenantId($this->getTenantId($data->getState()));
        $entity->setAccessToken($token->getAccessToken());
        $entity->setRefreshToken($token->getRefreshToken());
        $entity->setExpiresIn($token->getExpiresIn());
        $entity->setCreatedAt(time());

        $this->repository->save($entity);

        try {
            $apiKey = $this->getApiKey($token->getAccessToken());

            $this->repository->delete($entity);
        } catch (HttpAuthenticationException $e) {
            if(!$this->isTokenExpired($entity)) {
                throw $e;
            }

            $refreshedToken = $this->refreshToken($token->getRefreshToken());
            $entity->setAccessToken($refreshedToken->getAccessToken());
            $entity->setRefreshToken($refreshedToken->getRefreshToken());
            $entity->setExpiresIn($refreshedToken->getExpiresIn());
            $entity->setCreatedAt(time());

            $this->repository->update($entity);

            $apiKey = $this->getApiKey($refreshedToken->getAccessToken());
        }

        return $apiKey;
    }

    /**
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getApiKey($accessToken)
    {
        return $this->getPacklinkProxy()->getApiKeyWithToken($accessToken);
    }

    /**
     * @param $authorizationCode
     *
     * @return \Packlink\BusinessLogic\Http\DTO\OAuthToken
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getToken($authorizationCode)
    {
        return $this->proxy->getAuthToken($authorizationCode);
    }

    /**
     * @throws HttpCommunicationException
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     */
    public function refreshToken($refreshToken)
    {
        return $this->proxy->refreshAuthToken($refreshToken);
    }

    /**
     * @param OAuthUrlData $data
     *
     * @return string
     */
    public function buildRedirectUrlAndSaveState(OAuthUrlData $data)
    {
        $queryParams = array(
            'response_type' => 'code',
            'client_id'     => $data->getClientId(),
            'redirect_uri'  => $data->getRedirectUri(),
            'scope'         => implode(' ', $data->getScopes()),
            'state'         => $this->saveState($data->getTenantId()),
        );

        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $domain = TenantDomainProvider::getDomain($data->getDomain());

        return 'https://' . rtrim($domain, '/') . '/auth/oauth2/authorize?' . $queryString;
    }

    /**
     * @param $state
     *
     * @return string
     */
    public function getTenantId($state)
    {
        return $this->stateService->extractTenantIdFromState($state);
    }

    /**
     * @param $tenantId
     *
     * @return mixed
     */
    private function saveState($tenantId)
    {
        return $this->stateService->generateAndSaveState($tenantId);
    }

    /**
     * @param OAuthInfo $tokenEntity
     *
     * @return bool
     */
    private function isTokenExpired(OAuthInfo $tokenEntity)
    {
        return (time() >= ($tokenEntity->getCreatedAt() + $tokenEntity->getExpiresIn()));
    }

    /**
     * @return Proxy
     */
    private function getPacklinkProxy()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(Proxy::CLASS_NAME);
    }
}