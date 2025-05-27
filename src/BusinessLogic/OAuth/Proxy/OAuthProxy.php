<?php

namespace Packlink\BusinessLogic\OAuth\Proxy;

use Exception;
use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\Logger\Logger;
use Packlink\BusinessLogic\Http\DTO\OAuthToken;
use Packlink\BusinessLogic\OAuth\Proxy\Interfaces\OAuthProxyInterface;
use Packlink\BusinessLogic\OAuth\Services\OAuthConfiguration;
use Packlink\BusinessLogic\OAuth\Services\TenantDomainProvider;

class OAuthProxy implements OAuthProxyInterface
{
    /**
     * HTTP Client.
     *
     * @var HttpClient
     */
    private $client;

    /**
     * @var \Packlink\BusinessLogic\OAuth\Services\OAuthConfiguration
     */
    private $config;

    /**
     * Base URL for token endpoint.
     *
     * @var string
     */
    private $baseUrl;


    /**
     * OAuthProxy constructor.
     *
     * @param \Packlink\BusinessLogic\OAuth\Services\OAuthConfiguration
     * @param HttpClient $client
     */
    public function __construct(OAuthConfiguration $config, HttpClient $client)
    {
        $this->config = $config;
        $this->client = $client;

        $this->baseUrl = 'https://' . TenantDomainProvider::getDomain($config->getDomain()) . '/auth/oauth2/';
    }

    /**
     * @param $authorizationCode
     *
     * @return OAuthToken
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getAuthToken($authorizationCode)
    {
        return $this->requestToken(array(
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'redirect_uri' => $this->config->getRedirectUri(),
        ));
    }

    /**
     * @param $refreshToken
     *
     * @return OAuthToken
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function refreshAuthToken($refreshToken)
    {
        return $this->requestToken(array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ));
    }

    /**
     * @param array $body
     *
     * @return OAuthToken
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    private function requestToken(array $body)
    {
        try {
            $response = $this->call(HttpClient::HTTP_METHOD_POST, 'token', $body);
            $data = $response->decodeBodyToArray();

            return new OAuthToken(
                isset($data['access_token']) ? $data['access_token'] : '',
                isset($data['token_type']) ? $data['token_type'] : '',
                isset($data['expires_in']) ? (int) $data['expires_in'] : 0,
                isset($data['refresh_token']) ? $data['refresh_token'] : ''
            );
        } catch (Exception $e) {
            Logger::logError('OAuth token request failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Makes a HTTP call and returns response.
     *
     * @param string $method HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint Endpoint resource on remote API.
     * @param array $body Request payload body.
     *
     * @return HttpResponse Response from request.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function call($method, $endpoint, array $body = array())
    {
        $bodyStringToSend = '';
        if (in_array(strtoupper($method), array(HttpClient::HTTP_METHOD_POST, HttpClient::HTTP_METHOD_PUT), true)) {
            $bodyStringToSend = http_build_query($body);
        }

        $response = $this->client->request(
            $method,
            $this->baseUrl . ltrim($endpoint, '/'),
            $this->getRequestHeaders(),
            $bodyStringToSend
        );

        $this->validateResponse($response);

        return $response;
    }

    /**
     * Validates HTTP response.
     *
     * @param HttpResponse $response HTTP response returned from API call.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function validateResponse(HttpResponse $response)
    {
        if (!$response->isSuccessful()) {
            $httpCode = $response->getStatus();
            $body = $response->decodeBodyToArray();
            $message = 'Unknown error.';

            if (is_array($body)) {
                if (!empty($body['error_description'])) {
                    $message = $body['error_description'];
                } elseif (!empty($body['error'])) {
                    $message = $body['error'];
                }
            }

            Logger::logInfo('OAuth error: ' . $message);

            if ($httpCode === 401) {
                throw new HttpAuthenticationException($message, $httpCode);
            }

            throw new HttpRequestException($message, $httpCode);
        }
    }

    /**
     * Returns headers together with authorization entry.
     *
     * @return array Formatted request headers.
     */
    private function getRequestHeaders()
    {
        $clientId = $this->config->getClientId();
        $clientSecret = $this->config->getClientSecret();

        $encodedCredentials = base64_encode($clientId . ':' . urlencode($clientSecret));

        return array(
            'Authorization' => 'Authorization: Basic ' . $encodedCredentials,
            'Content-Type' => 'Content-Type: application/x-www-form-urlencoded',
        );
    }
}
