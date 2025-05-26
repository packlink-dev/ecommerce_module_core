<?php

namespace Packlink\BusinessLogic\Http;

use Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpBaseException;
use Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Logeecom\Infrastructure\Http\Exceptions\HttpRequestException;
use Logeecom\Infrastructure\Http\HttpClient;
use Logeecom\Infrastructure\Http\HttpResponse;
use Logeecom\Infrastructure\Logger\Logger;
use Logeecom\Infrastructure\ServiceRegister;
use Packlink\BusinessLogic\Brand\BrandConfigurationService;
use Packlink\BusinessLogic\Configuration;
use Packlink\BusinessLogic\DTO\FrontDtoFactory;
use Packlink\BusinessLogic\Http\DTO\Analytics;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsInvoice;
use Packlink\BusinessLogic\Http\DTO\Customs\CustomsUnionsSearchRequest;
use Packlink\BusinessLogic\Http\DTO\Draft;
use Packlink\BusinessLogic\Http\DTO\DropOff;
use Packlink\BusinessLogic\Http\DTO\LocationInfo;
use Packlink\BusinessLogic\Http\DTO\ParcelInfo;
use Packlink\BusinessLogic\Http\DTO\PostalCode;
use Packlink\BusinessLogic\Http\DTO\PostalZone;
use Packlink\BusinessLogic\Http\DTO\Shipment;
use Packlink\BusinessLogic\Http\DTO\ShippingService;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceDetails;
use Packlink\BusinessLogic\Http\DTO\ShippingServiceSearch;
use Packlink\BusinessLogic\Http\DTO\Tracking;
use Packlink\BusinessLogic\Http\DTO\User;
use Packlink\BusinessLogic\Http\Exceptions\DraftNotCreatedException;
use Packlink\BusinessLogic\Utility\Php\Php55;
use Packlink\BusinessLogic\Warehouse\Warehouse;

/**
 * Class Proxy. In charge for communication with Packlink API.
 *
 * @package Packlink\BusinessLogic
 */
class Proxy
{
    /**
     * Fully qualified name of this class.
     */
    const CLASS_NAME = __CLASS__;
    /**
     * Packlink base API URL.
     */
    const BASE_URL = 'https://api.packlink.com/';
    /**
     * Packlink API version
     */
    const API_VERSION = 'v1/';
    /**
     * HTTP Client.
     *
     * @var HttpClient
     */
    private $client;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * Proxy constructor.
     *
     * @param Configuration $configService Configuration service.
     * @param HttpClient $client System HTTP client.
     */
    public function __construct(Configuration $configService, HttpClient $client)
    {
        $this->client = $client;
        $this->configService = $configService;
    }

    /**
     * Returns a list of user parcels information.
     *
     * @return ParcelInfo[] Array of parcels information.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getUsersParcelInfo()
    {
        $response = $this->call(HttpClient::HTTP_METHOD_GET, 'users/parcels');
        $data = $response->decodeBodyToArray() ?: array();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return FrontDtoFactory::getFromBatch(ParcelInfo::CLASS_KEY, $data);
    }

    /**
     * Returns a list of user warehouses.
     *
     * @return Warehouse[] Array of warehouses.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getUsersWarehouses()
    {
        $response = $this->call(HttpClient::HTTP_METHOD_GET, 'clients/warehouses');
        $data = $response->decodeBodyToArray() ?: array();

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return FrontDtoFactory::getFromBatch(Warehouse::CLASS_KEY, $data);
    }

    /**
     * Attemps to register a new user account on Packlink PRO API.
     *
     * @param array $data
     *
     * @return string Authorization token of the new account if it was created.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function register($data)
    {
        $data['referral'] = array(
            'onboarding_product' => $this->configService->getIntegrationName(),
            'onboarding_sub_product' => 'marketplace',
        );

        $response = $this->call(HttpClient::HTTP_METHOD_POST, 'register', $data);

        $data = $response->decodeBodyToArray();

        return !empty($data['token']) ? $data['token'] : '';
    }

    /**
     * Returns user info.
     *
     * @return User User info.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getUserData()
    {
        $response = $this->call(HttpClient::HTTP_METHOD_GET, 'clients');

        return User::fromArray($response->decodeBodyToArray());
    }

    /**
     * Subscribes web-hook callback url.
     *
     * @param string $webHookUrl Web-hook URL.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function registerWebHookHandler($webHookUrl)
    {
        $this->call(HttpClient::HTTP_METHOD_POST, 'shipments/callback', array('url' => $webHookUrl));
    }

    /**
     * Returns top ten drop-off locations in postal code area.
     *
     * @param string $serviceId Unique shipping service identifier.
     * @param string $countryCode Country ISO2 code.
     * @param string $postalCode Postal/ZIP code.
     *
     * @return DropOff[] List of all drop off locations near given postal code.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getLocations($serviceId, $countryCode, $postalCode)
    {
        $response = $this->call(HttpClient::HTTP_METHOD_GET, urlencode("dropoffs/$serviceId/$countryCode/$postalCode"));

        return DropOff::fromBatch($response->decodeBodyToArray() ?: array());
    }

    /**
     * Performs search for locations.
     *
     * @param string $platformCountry Country code to search in.
     * @param string $postalZone Postal zone to search in.
     * @param string $query Query to search for.
     *
     * @return \Packlink\BusinessLogic\Http\DTO\LocationInfo[]
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function searchLocations($platformCountry, $postalZone, $query)
    {
        $brand = $this->getBrandConfigurationService()->get();

        $url = 'locations/postalcodes?' . http_build_query(
                array(
                    'platform' => $brand->platformCode,
                    'platform_country' => $platformCountry,
                    'postalzone' => $postalZone,
                    'q' => $query,
                )
            );

        $response = $this->call(HttpClient::HTTP_METHOD_GET, $url);

        return LocationInfo::fromBatch($response->decodeBodyToArray());
    }

    /**
     * Returns array of PostalCode objects by specified country and specified zip code.
     *
     * @param string $countryCode Two-letter iso code of a country.
     * @param string $zipCode Zip code.
     *
     * @return PostalCode[] PostalCode DTO.
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getPostalCodes($countryCode, $zipCode)
    {
        $response = $this->call(HttpClient::HTTP_METHOD_GET, urlencode("locations/postalcodes/$countryCode/$zipCode"));

        return PostalCode::fromBatch($response->decodeBodyToArray());
    }

    /**
     * Returns array of PostalZone objects by specified country.
     *
     * @param string $countryCode
     * @param string $lang
     *
     * @return \Packlink\BusinessLogic\Http\DTO\PostalZone[]
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getPostalZones($countryCode, $lang = 'en')
    {
        $brand = $this->getBrandConfigurationService()->get();

        $url = 'locations/postalzones/destinations?' . http_build_query(
                array(
                    'platform' => $brand->platformCode,
                    'platform_country' => $countryCode,
                    'language' => $lang,
                )
            );

        $response = $this->call(HttpClient::HTTP_METHOD_GET, $url);

        $postalZones = PostalZone::fromBatch($response->decodeBodyToArray());

        $postalZones = array_filter(
            $postalZones,
            function ($postalZone) use ($countryCode) {
                /** @var PostalZone $postalZone */
                return $postalZone->isoCode === $countryCode;
            }
        );

        return $postalZones;
    }

    /**
     * Gets available shipping services delivery details for given search data.
     *
     * @param ShippingServiceSearch $params Search parameters.
     *
     * @return ShippingServiceDetails[] Found services with details.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getShippingServicesDeliveryDetails(ShippingServiceSearch $params)
    {
        if (!$params->isValid()) {
            Logger::logDebug('Missing required search parameter(s).', 'Core', $params->toArray());
            throw new HttpRequestException('Missing required search parameter(s).', 400);
        }

        $response = $this->call(HttpClient::HTTP_METHOD_GET, 'services?' . http_build_query($params->toArray()));

        $body = $response->decodeBodyToArray();
        if (empty($body)) {
            return array();
        }

        $shippingDetails = ShippingServiceDetails::fromBatch($body);

        if (!$this->configService->dropOffShippingServicesSupported()) {
            return $this->getShippingServicesDeliveryDetailsWithoutDropOffServices($shippingDetails, $params);
        }

        foreach ($shippingDetails as $shippingDetail) {
            $shippingDetail->departureCountry = $params->fromCountry;
            $shippingDetail->destinationCountry = $params->toCountry;
            $shippingDetail->national = $params->toCountry === $params->fromCountry;
        }

        return $shippingDetails;
    }

    /**
     * Gets details about the service.
     *
     * @param int $id Service Id.
     *
     * @return ShippingService Shipping service.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getShippingServiceDetails($id)
    {
        $response = $this->call(HttpClient::HTTP_METHOD_GET, "services/available/$id/details");

        return ShippingService::fromArray($response->decodeBodyToArray());
    }

    /**
     * Sends shipment draft to Packlink.
     *
     * @param Draft $draft Shipment draft.
     *
     * @return string Shipment reference for uploaded draft.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     * @throws \Packlink\BusinessLogic\Http\Exceptions\DraftNotCreatedException
     */
    public function sendDraft(Draft $draft)
    {
        $response = $this->call(HttpClient::HTTP_METHOD_POST, 'shipments', $draft->toArray());

        $result = $response->decodeBodyToArray();
        $reference = array_key_exists('reference', $result) ? $result['reference'] : '';

        if (!$reference) {
            Logger::logError(
                'Draft reference not returned by Packlink API.',
                'Core',
                array(
                    'Draft data' => $draft->toArray(),
                    'Response' => $response,
                )
            );

            throw new DraftNotCreatedException('Draft reference not returned by Packlink API.');
        }

        $this->sendAnalytics(Analytics::EVENT_DRAFT_CREATED);

        return $reference;
    }

    /**
     * Returns shipment by its reference identifier.
     *
     * @param string $referenceId Packlink shipment reference identifier.
     *
     * @return Shipment|null Shipment DTO if it exists for given reference number; otherwise, null.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getShipment($referenceId)
    {
        $response = $this->getShipmentData($referenceId);

        return $response !== null ? Shipment::fromArray($response->decodeBodyToArray()) : null;
    }

    /**
     * Returns list of shipment labels for shipment with provided reference.
     *
     * @param string $referenceId Packlink shipment reference identifier.
     *
     * @return string[] Array of shipment labels.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getLabels($referenceId)
    {
        $response = $this->getShipmentData($referenceId, 'labels');

        return $response !== null ? $response->decodeBodyToArray() : array();
    }

    /**
     * Returns tracking information by its reference identifier.
     *
     * @param string $referenceId Packlink shipment reference identifier.
     *
     * @return Tracking[] Tracking DTO.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    public function getTrackingInfo($referenceId)
    {
        $response = $this->getShipmentData($referenceId, 'track');

        return $response !== null ? Tracking::fromBatch($response->decodeBodyToArray()) : array();
    }

    /**
     * Sends the analytics data. Includes current integrated system name and version.
     *
     * @param string $eventName The name of the event to send.
     */
    public function sendAnalytics($eventName)
    {
        $data = new Analytics(
            $eventName,
            $this->configService->getECommerceName(),
            $this->configService->getECommerceVersion(),
            $this->configService->getModuleVersion()
        );

        try {
            $this->call(HttpClient::HTTP_METHOD_POST, 'analytics', $data->toArray());
        } catch (HttpBaseException $e) {
            Logger::logWarning('Could not send analytics data. Exception: ' . $e->getMessage());
        }
    }

    /**
     * Search the known customs unions between two postal codes.
     *
     * @param CustomsUnionsSearchRequest $request
     *
     * @return array
     *
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws HttpCommunicationException
     */
    public function getCustomsByPostalCode(CustomsUnionsSearchRequest $request)
    {
        $result = $this->call(HttpClient::HTTP_METHOD_POST, '/customs-unions/search-by-postal-code', $request->toArray())
            ->decodeBodyToArray();

        return isset($result['data']) ? $result['data'] : array();
    }

    /**
     * Creates a new customs invoice.
     *
     * @param CustomsInvoice $customsInvoice
     *
     * @return string|null
     *
     * @throws HttpAuthenticationException
     * @throws HttpRequestException
     * @throws HttpCommunicationException
     */
    public function sendCustomsInvoice(CustomsInvoice $customsInvoice)
    {
        $result = $this->call(HttpClient::HTTP_METHOD_POST, '/customs-invoices', $customsInvoice->toArray())
            ->decodeBodyToArray();

        return isset($result['id']) ? $result['id'] : null;
    }

    /**
     * Retrieves customs invoice download url.
     *
     * @param $customsInvoiceId
     *
     * @return string
     *
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getCustomsInvoiceDownloadUrl($customsInvoiceId)
    {
        $result = $this->call(HttpClient::HTTP_METHOD_GET, "/customs-invoices/$customsInvoiceId/download")
            ->decodeBodyToArray();

        return isset($result['url']) ? $result['url'] : '';
    }

    /**
     * @param $accessToken
     *
     * @return string
     * @throws HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     */
    public function getApiKeyWithToken($accessToken)
    {
        $response = $this->callWithToken(HttpClient::HTTP_METHOD_GET, 'users/api/keys', $accessToken);

        $data = json_decode($response->getBody(), true);

        if (isset($data['token'])) {
            return $data['token'];
        }

        throw new HttpAuthenticationException('Could not retrieve API key.');
    }

    /**
     * @param $shippingDetails
     * @param $params
     * @return array
     */
    protected function getShippingServicesDeliveryDetailsWithoutDropOffServices($shippingDetails, $params)
    {
        $newShippingDetails = array();

        foreach ($shippingDetails as $shippingDetail) {
            if ($shippingDetail->destinationDropOff) {
                continue;
            } else {
                $shippingDetail->departureCountry = $params->fromCountry;
                $shippingDetail->destinationCountry = $params->toCountry;
                $shippingDetail->national = $params->toCountry === $params->fromCountry;
                $newShippingDetails[] = $shippingDetail;
            }
        }

        return $newShippingDetails;
    }

    /**
     * Calls shipments endpoint and handles response. Any shipment endpoint can return 404 so this call handles that.
     *
     * @param string $reference Shipment reference number.
     * @param string $endpoint Endpoint to call.
     *
     * @return \Logeecom\Infrastructure\Http\HttpResponse|null Response if API returned it; NULL if 404.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function getShipmentData($reference, $endpoint = '')
    {
        if ($endpoint) {
            $endpoint = '/' . $endpoint;
        }

        try {
            $response = $this->call(HttpClient::HTTP_METHOD_GET, "shipments/{$reference}{$endpoint}");
        } catch (HttpRequestException $e) {
            if ($e->getCode() === 404) {
                return null;
            }

            throw $e;
        }

        return $response;
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
            $bodyStringToSend = json_encode($body);
        }

        $response = $this->client->request(
            $method,
            static::BASE_URL . static::API_VERSION . ltrim($endpoint, '/'),
            $this->getRequestHeaders(),
            $bodyStringToSend
        );

        $this->validateResponse($response);

        return $response;
    }

    /**
     * Makes a HTTP call with Bearer token and returns response.
     *
     * @param string $method HTTP method (GET, POST, PUT, etc.).
     * @param string $endpoint Endpoint resource on remote API.
     * @param array $body Request payload body.
     * @param string $accessToken Bearer token for authentication.
     *
     * @return HttpResponse Response from request.
     *
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
     * @throws HttpCommunicationException
     * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
     */
    protected function callWithToken($method, $endpoint,  $accessToken, array $body = array())
    {
        $bodyStringToSend = '';
        if (in_array(strtoupper($method), array(HttpClient::HTTP_METHOD_POST, HttpClient::HTTP_METHOD_PUT), true)) {
            $bodyStringToSend = json_encode($body);
        }

        $headers = array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
            'token' => 'Authorization: ' . 'Bearer ' . $accessToken,
            'Module-Version' => 'X-Module-Version: ' . $this->configService->getModuleVersion(),
            'Ecommerce-Name' => 'X-Ecommerce-Name: ' . $this->configService->getECommerceName(),
            'Ecommerce-Version' => 'X-Ecommerce-Version: ' . $this->configService->getECommerceVersion(),
        );

        $response = $this->client->request(
            $method,
            static::BASE_URL . static::API_VERSION . ltrim($endpoint, '/'),
            $headers,
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
            $error = $message = $response->decodeBodyToArray();
            if (is_array($error)) {
                $message = '';
                if (isset($error['messages']) && is_array($error['messages'])) {
                    $message = implode("\n", Php55::arrayColumn($error['messages'], 'message'));
                    // if there is only one message, 'messages' is associative array
                    if (empty($message) && !empty($error['messages']['message'])) {
                        $message = $error['messages']['message'];
                    }
                } elseif (isset($error['message'])) {
                    $message = $error['message'];
                }
            }

            if ($httpCode === HttpClient::HTTP_STATUS_CODE_NOT_FOUND) {
                $message = '404 Not found.';
            }

            Logger::logInfo($message);
            if ($httpCode === HttpClient::HTTP_STATUS_CODE_UNAUTHORIZED) {
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
        return array(
            'accept' => 'Accept: application/json',
            'content' => 'Content-Type: application/json',
            'token' => 'Authorization: ' . $this->configService->getAuthorizationToken(),
            'Module-Version' => 'X-Module-Version: ' . $this->configService->getModuleVersion(),
            'Ecommerce-Name' => 'X-Ecommerce-Name: ' . $this->configService->getECommerceName(),
            'Ecommerce-Version' => 'X-Ecommerce-Version: ' . $this->configService->getECommerceVersion(),
        );
    }

    /**
     * @return BrandConfigurationService
     */
    private function getBrandConfigurationService()
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return ServiceRegister::getService(BrandConfigurationService::CLASS_NAME);
    }
}
