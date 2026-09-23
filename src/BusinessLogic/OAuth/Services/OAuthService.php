<?php

namespace Packlink\BusinessLogic\OAuth\Services;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Configuration;
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
    /**
     * Value the authorization server expects when the merchant sells on no marketplace (PKLPM-103).
     */
    const NO_MARKETPLACE = 'I do not have any marketplace outlets';

    /**
     * Integration names as the authorization server expects them, keyed by the lower-cased value
     * platforms report through `Configuration::getECommerceName()`.
     *
     * A map rather than `ucfirst()` because that would produce `Woocommerce` and `Bigcommerce`,
     * and the server matches on the exact display name.
     *
     * @var array
     */
    protected static $integrationNames = array(
        'shopify' => 'Shopify',
        'woocommerce' => 'WooCommerce',
        'prestashop' => 'PrestaShop',
        'magento' => 'Magento',
        'wix' => 'Wix',
        'ecwid' => 'Ecwid',
        'bigcommerce' => 'BigCommerce',
    );

    /** @var OAuthProxy */
    protected $proxy;

    /**
     * @var Proxy
     */
    protected $packlinkProxy;

    /**
     * @var RepositoryInterface
     */
    protected $repository;
    /**
     * @var OAuthStateServiceInterface
     */
    protected $stateService;
    /**
     * @var OAuthConfiguration
     */
    protected $configuration;


    public function __construct(
        OAuthProxyInterface $proxy,
        Proxy $packlinkProxy,
        RepositoryInterface $repository,
        OAuthStateServiceInterface $stateService,
        OAuthConfiguration $configuration
    ) {
        $this->proxy = $proxy;
        $this->packlinkProxy = $packlinkProxy;
        $this->repository = $repository;
        $this->stateService = $stateService;
        $this->configuration = $configuration;
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
        return $this->packlinkProxy->getApiKeyWithToken($accessToken);
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
     * @param string $domain
     *
     * @return string
     */
    public function buildRedirectUrlAndSaveState($domain)
    {
        $queryParams = array(
            'response_type' => 'code',
            'client_id'     => $this->configuration->getClientId(),
            'redirect_uri'  => $this->configuration->getRedirectUri(),
            'scope'         => implode(' ', $this->configuration->getScopes()),
            'state'         => $this->saveState($this->configuration->getTenantId()),
        );

        // PKLPM-103. A merchant without an account registers from inside this screen rather than
        // through the module's own form, and without these three the authorization server cannot tell
        // where that registration came from: `onboarding_product` is left empty and the lead is never
        // classified. `origin` is what lands in that field; `ecommerce` and `marketplace` prefill form
        // fields so the merchant is not asked for what we already know.
        //
        // Omitted entirely when the integration cannot be named, so the URL is then byte-identical to
        // what it was before - an empty `origin` would be worse than none at all.
        $integrationName = $this->getIntegrationName();

        if ($integrationName !== '') {
            $queryParams['origin'] = $integrationName;
            $queryParams['ecommerce'] = $integrationName;
            $queryParams['marketplace'] = self::NO_MARKETPLACE;
        }

        // PHP_QUERY_RFC3986 escapes the values, which is what the server expects of `marketplace`.
        $queryString = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

        $domain = TenantDomainProvider::getDomain($domain);

        return 'https://' . rtrim($domain, '/') . '/auth/oauth2/authorize?' . $queryString;
    }

    /**
     * Name of this integration as the authorization server expects it ("Shopify", "WooCommerce", ...).
     *
     * Read from `Configuration::getECommerceName()`, which every platform already implements, rather
     * than from a new abstract method on `OAuthConfiguration`: adding one would fatal every module
     * that had not implemented it yet, and this has to ship without every platform moving first.
     *
     * A platform the map does not know falls back to whatever it reports, which still carries the
     * information even if the casing is off. A name that cannot be read at all yields an empty string
     * and the caller then leaves the parameters out.
     *
     * @return string
     */
    protected function getIntegrationName()
    {
        try {
            /** @var Configuration $configService */
            $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
            $name = trim((string)$configService->getECommerceName());
        } catch (\Exception $e) {
            return '';
        }

        if ($name === '') {
            return '';
        }

        $key = strtolower($name);

        return isset(static::$integrationNames[$key]) ? static::$integrationNames[$key] : $name;
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
}