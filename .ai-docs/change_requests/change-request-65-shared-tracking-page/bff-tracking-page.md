# Core update — BFF "shared tracking page" proxy methods

## Context

CR-62 (Shopify PUDO improvements) added a **shareable / public tracking page** link to the
admin order-details modal. Producing that link requires talking to the Packlink **BFF API**:

```
App  -->  GET /v1/shipments/{reference}              -->  Packlink v1 API
App  <--  { order_reference: "..." }                 <--  Packlink v1 API

App  -->  GET /bff/init                               -->  Packlink BFF API
App  <--  { sessionId: "..." }                        <--  Packlink BFF API

App  -->  GET /bff/postsale/{orderRef}/{ref}?locale=  -->  Packlink BFF API
App  <--  BffPostsaleResponse (publicTrackingUrl)     <--  Packlink BFF API

App  -->  GET /bff/tracking/public/{trackingRef}      -->  Packlink BFF API
App  <--  BffTrackingResponse (estimatedDeliveryDate) <--  Packlink BFF API
```

All of this currently lives in the Shopify-side `app/Http/PacklinkProxy.php` (which
`extends Packlink\BusinessLogic\Http\Proxy`) plus three DTOs under `app/Http/DTO/BFF/`.
**None of it is Shopify-specific** — it is pure Packlink-API interaction — so it belongs
in `integration-core` where every platform integration can reuse it.

Introduced in:
- `fb529d9` "Implement PUDO on admin order page" — proxy methods + BFF DTOs.
- `6be2873` "Fix missing note for not drop off selected" — controller/route/frontend wiring (stays app-side).

## Decisions

| Decision | Choice |
|----------|--------|
| Target | `vendor/.../BusinessLogic/Http/Proxy.php` + new `Http/DTO/BFF/` folder |
| HTTP client | Reuse core `$this->client` (drop app's `getClient()` / `ServiceRegister`) |
| Auth header | Reuse core `$this->configService->getAuthorizationToken()` (compare `getRequestHeaders()`) |
| BFF base URL | Reuse `static::BASE_URL` (no `API_VERSION` prefix — same as `callBase()`) |
| Error handling | Reuse core `validateResponse()` (typed exceptions) instead of raw `RuntimeException` |
| DTO style | Extend core `DataTransferObject` to match existing DTOs (e.g. `Http/DTO/Tracking.php`) |

---

## 1. Proxy methods → `BusinessLogic/Http/Proxy.php`

Add a `$bffSessionId` instance property next to the existing private members, then add the
public methods and the private BFF plumbing. Rewritten to use the base-class members
(`$this->client`, `$this->configService`, `validateResponse()`) rather than the
`ServiceRegister` lookups the app version used.

```php
/**
 * BFF session id, cached per request lifecycle.
 *
 * @var string|null
 */
private $bffSessionId = null;

/**
 * Gets the order_reference for a shipment via the v1 API.
 *
 * @param string $reference Shipment reference.
 *
 * @return string|null Order reference, or null if not found.
 *
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
 */
public function getOrderReference($reference)
{
    $response = $this->call(HttpClient::HTTP_METHOD_GET, 'shipments/' . $reference);
    $data = $response->decodeBodyToArray();

    return isset($data['order_reference']) ? $data['order_reference'] : null;
}

/**
 * Gets the public (shareable) tracking page URL for a shipment via the BFF postsale endpoint.
 *
 * Flow: v1 API (order_reference) -> BFF init (session) -> BFF postsale (tracking URL).
 *
 * @param string $reference Shipment reference.
 * @param string $locale    Locale for the tracking page, e.g. 'en-GB'.
 *
 * @return string|null Public tracking URL, or null if unavailable.
 *
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
 */
public function getPublicTrackingUrl($reference, $locale = 'en-GB')
{
    $orderReference = $this->getOrderReference($reference);
    if (!$orderReference) {
        return null;
    }

    $response = $this->callBff("postsale/{$orderReference}/{$reference}?locale={$locale}");
    $postsale = BffPostsaleResponse::fromArray($response->decodeBodyToArray());

    return $postsale->publicTrackingUrl ?: null;
}

/**
 * Gets the estimated delivery date from the BFF public tracking endpoint.
 *
 * @param string $publicTrackingUrl Full public tracking URL containing the tracking ref.
 *
 * @return string|null Estimated delivery date, or null if unavailable.
 *
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
 */
public function getEstimatedDeliveryDate($publicTrackingUrl)
{
    $trackingRef = basename(parse_url($publicTrackingUrl, PHP_URL_PATH));
    if (!$trackingRef) {
        return null;
    }

    $response = $this->callBff("tracking/public/{$trackingRef}");
    $tracking = BffTrackingResponse::fromArray($response->decodeBodyToArray());

    return $tracking->estimatedDeliveryDate ?: null;
}

/**
 * Initializes a BFF session (GET /bff/init). Cached per request lifecycle.
 *
 * @return string Session id.
 *
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
 */
private function initBffSession()
{
    if ($this->bffSessionId !== null) {
        return $this->bffSessionId;
    }

    $response = $this->client->request(
        HttpClient::HTTP_METHOD_GET,
        static::BASE_URL . 'bff/init',
        $this->getBffHeaders()
    );
    $this->validateResponse($response);

    $session = BffSessionResponse::fromArray($response->decodeBodyToArray());
    $this->bffSessionId = $session->sessionId;

    return $this->bffSessionId;
}

/**
 * Calls a BFF endpoint (no API version prefix) with the session id header.
 *
 * @param string $endpoint BFF endpoint path, e.g. 'tracking/public/{ref}'.
 *
 * @return HttpResponse HTTP response.
 *
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpAuthenticationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpCommunicationException
 * @throws \Logeecom\Infrastructure\Http\Exceptions\HttpRequestException
 */
private function callBff($endpoint)
{
    $headers = $this->getBffHeaders();
    $headers['session'] = 'X-Packlink-Session-Id: ' . $this->initBffSession();

    $response = $this->client->request(
        HttpClient::HTTP_METHOD_GET,
        static::BASE_URL . 'bff/' . ltrim($endpoint, '/'),
        $headers
    );
    $this->validateResponse($response);

    return $response;
}

/**
 * Returns base headers for BFF API calls.
 *
 * @return array
 */
private function getBffHeaders()
{
    return array(
        'token' => 'Authorization: ' . $this->configService->getAuthorizationToken(),
        'accept' => 'Accept: application/json',
        'content-type' => 'Content-Type: application/json',
    );
}
```

> Note: the app version used a bespoke `validateBffResponse()` that threw a raw
> `RuntimeException` and a `getClient()` helper resolved through `ServiceRegister`. Both are
> dropped above in favour of the existing core `validateResponse()` and `$this->client`.
> `getOrderReference()` already routes through the existing `call()` (v1 / API_VERSION),
> and `callBff()` mirrors `callBase()` (BASE_URL, no version) plus the session header.

---

## 2. DTOs → new `BusinessLogic/Http/DTO/BFF/`

Moved verbatim from `app/Http/DTO/BFF/`, re-namespaced to
`Packlink\BusinessLogic\Http\DTO\BFF`. Shown here as PHP 8 promoted-constructor classes
(as written in the app). If the core's PHP floor predates 8.0, convert to plain
properties + a constructor, or extend `DataTransferObject` like the existing
`Http/DTO/Tracking.php`.

### `BffSessionResponse.php`

```php
<?php

namespace Packlink\BusinessLogic\Http\DTO\BFF;

class BffSessionResponse
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $tenantName = '',
        public readonly string $platform = '',
        public readonly string $platformCountry = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            sessionId: $data['sessionId'] ?? '',
            tenantName: $data['tenantName'] ?? '',
            platform: $data['platform'] ?? '',
            platformCountry: $data['platformCountry'] ?? '',
        );
    }
}
```

### `BffPostsaleResponse.php`

```php
<?php

namespace Packlink\BusinessLogic\Http\DTO\BFF;

class BffPostsaleResponse
{
    public function __construct(
        public readonly string $publicTrackingUrl = '',
        public readonly string $orderReference = '',
        public readonly bool $isDropOff = false,
        public readonly string $carrierIcon = '',
        public readonly string $serviceName = '',
        public readonly string $shipmentStatusLabel = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        $shipmentData = $data['shipmentData'] ?? [];
        $details = $shipmentData['shipmentDetails'] ?? [];
        $service = $shipmentData['service'] ?? [];
        $status = $shipmentData['shipmentStatus'] ?? [];

        return new self(
            publicTrackingUrl: $details['publicTrackingUrl'] ?? '',
            orderReference: $details['orderReference'] ?? '',
            isDropOff: $details['isDropOff'] ?? false,
            carrierIcon: $service['carrierIcon'] ?? '',
            serviceName: $service['name'] ?? '',
            shipmentStatusLabel: $status['label'] ?? '',
        );
    }
}
```

### `BffTrackingResponse.php`

```php
<?php

namespace Packlink\BusinessLogic\Http\DTO\BFF;

class BffTrackingResponse
{
    public function __construct(
        public readonly string $estimatedDeliveryDate = '',
        public readonly string $status = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        $parcels = $data['parcels'] ?? [];
        $firstParcel = $parcels[0] ?? [];
        $currentStatus = $firstParcel['currentStatus'] ?? [];

        return new self(
            estimatedDeliveryDate: $firstParcel['estimatedDeliveryDate'] ?? '',
            status: $currentStatus['label']['key'] ?? '',
        );
    }
}
```

Add the matching `use` statements to `Proxy.php`:

```php
use Packlink\BusinessLogic\Http\DTO\BFF\BffSessionResponse;
use Packlink\BusinessLogic\Http\DTO\BFF\BffPostsaleResponse;
use Packlink\BusinessLogic\Http\DTO\BFF\BffTrackingResponse;
```

---

## 3. What stays in the app (out of scope)

These are platform / HTTP-layer concerns and must **not** move to core. After the move
they simply call the inherited proxy methods:

| File | Why it stays |
|------|--------------|
| `app/Http/Controllers/PacklinkOrdersController::publicTrackingUrl()` | Laravel HTTP endpoint |
| `routes/api.php:964` (`order/tracking/public`) | Laravel route |
| `resources/react/js/services/OrderService.ts::getPublicTrackingUrl` | Frontend fetch |
| `resources/react/js/components/Orders/OrderList.tsx` (modal pre-fetch) | Frontend |
| `resources/react/js/components/Orders/OrderDetailsModal.tsx` ("shareable tracking" slot) | Frontend |
| `app/Service/BusinessLogic/OrderDetails/OrderDetailsService::fetchBffData()` + `DTO/BffData` | Shopify order-details aggregation |

After upstreaming, `app/Http/PacklinkProxy.php` keeps **only** its Shopify-specific
`getShippingServicesDeliveryDetails()` override (metrics) and the three BFF methods are
deleted from it — they are inherited from core. The three `app/Http/DTO/BFF/*` files are
deleted in favour of the core ones, and every `use Packlink\Shopify\Http\DTO\BFF\...`
import is repointed to `Packlink\BusinessLogic\Http\DTO\BFF\...`.

---

## 4. Verification checklist

- [ ] `getPublicTrackingUrl` / `getEstimatedDeliveryDate` / `getOrderReference` resolve from
      the base `Proxy` (no longer redeclared in `PacklinkProxy`).
- [ ] BFF DTO imports across the app point at `Packlink\BusinessLogic\Http\DTO\BFF`.
- [ ] `OrderDetailsService::fetchBffData()` still returns a populated `BffData` (manual:
      open the order-details modal for a shipped order; "shareable tracking" link + ETA show).
- [ ] BFF auth header matches `getRequestHeaders()` (same `getAuthorizationToken()`).
- [ ] BFF failures now surface as `HttpRequestException` / `HttpAuthenticationException`
      and are still swallowed by the app's `try/catch` in the controller/service.
- [ ] Static analysis / `composer` autoload regenerated after the namespace move.
