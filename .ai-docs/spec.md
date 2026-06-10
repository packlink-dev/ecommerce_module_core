# Packlink Integration Core — Functional Specification

> All use cases, acceptance criteria, validation rules, and business rules derived from the source code.

---

## 1. Registration

### 1.1 Registration Request Fields

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `email` | string | Yes | Valid email format (`DtoValidator::isEmailValid`) |
| `password` | string | Yes | Min 12 chars, requires lowercase + uppercase + digit + special char. Regex: `/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{12,}$/` |
| `estimated_delivery_volume` | string | Yes | Exact match one of: `"1 - 10"`, `"11 - 50"`, `"51 - 100"`, `"101 - 200"`, `"> 200"` |
| `phone` | string | Yes | Valid phone format (`DtoValidator::isPhoneValid`) |
| `language` | string | Yes | One of: `en_GB`, `de_DE`, `es_ES`, `fr_FR`, `it_IT`, `nl_NL` |
| `platform` | string | Yes | Must equal `brand->platformCode` exactly |
| `platform_country` | string | Yes | Must be in `brand->platformCountries` (typically: ES, DE, FR, IT, UN) |
| `source` | string | Yes | Valid URL (`filter_var` with `FILTER_VALIDATE_URL`) |
| `policies` | object | Yes | RegistrationLegalPolicy (see below) |
| `ecommerces` | array | Yes | Array of selected online stores |
| `marketplaces` | array | No | Array of selected marketplaces (default: empty) |

### 1.2 Legal Policy Validation

| Field | Must Be |
|-------|---------|
| `data_processing` | `true` (fails if false) |
| `terms_and_conditions` | `true` (fails if false) |
| `marketing_emails` | boolean (any value) |
| `marketing_calls` | boolean (any value) |

### 1.3 Registration Flow

1. Extract `platform` from brand configuration, inject into payload
2. Derive `language` from `platform_country`: ES→es_ES, DE→de_DE, FR→fr_FR, IT→it_IT, other→en_GB
3. Normalize `source` URL: strip existing http(s)://, prepend `https://`
4. Map policies: `data_processing` = same as `terms_and_conditions`; `marketing_calls` = always `true`
5. Validate payload via `RegistrationRequest::fromArray()`
6. Call `Proxy::register()` → returns authorization token
7. Auto-login with returned token via `UserAccountService::login()`
8. Return `true` only if both registration AND login succeed

### 1.4 Error Cases

- `FrontDtoValidationException` — field-level validation errors (array of `{field, code, message}`)
- `UnableToRegisterAccountException` — API call failed (wraps HTTP errors)

---

## 2. API Key Login

### 2.1 Flow

1. If API key is empty → return `false`
2. `Configuration::setAuthorizationToken($apiKey)`
3. `Proxy::getUserData()` → fetch user info from Packlink API
4. If HTTP error → `resetAuthorizationCredentials()`, return `false`
5. **Initialize user:**
   - Validate user country is in `brand->platformCountries` (throws `PlatformCountryNotSupportedByBrandException` if not)
   - `Configuration::setUserInfo($user)`
   - Fetch and set default parcel (from API, find one with `default=true`)
   - Fetch and set default warehouse (from API, find one with `default=true`, verify country is supported)
   - If warehouse set → enqueue `UpdateShippingServicesBusinessTask`
   - If webhook URL configured → `Proxy::registerWebHookHandler($url)`
   - `Proxy::sendAnalytics('api_configuration')`
6. **Create schedules:**
   - Schedule weekly `UpdateShippingServicesBusinessTask` (random day 1-7, hour 0-5, minute 0-59)
7. Return `true`

### 2.2 Acceptance Criteria

- AC1: Empty API key returns `false` without any API calls
- AC2: Invalid API key (HTTP error from getUserData) resets credentials and returns `false`
- AC3: User country not in brand's platformCountries throws `PlatformCountryNotSupportedByBrandException`
- AC4: Successful login stores user info, parcel, warehouse, and enqueues shipping sync
- AC5: Webhook is registered only if `getWebHookUrl()` returns non-empty
- AC6: Weekly shipping sync schedule is created with randomized timing

---

## 3. OAuth Flow

### 3.1 Authorization URL Generation

1. Generate random 64-hex-character state string
2. Encode as base64 JSON: `{"tenantId": "...", "state": "..."}`
3. Save `OAuthState` entity (tenantId + state) to repository
4. Build URL: `https://{domain}/auth/oauth2/authorize?response_type=code&client_id=...&redirect_uri=...&scope=...&state=...`

### 3.2 Code Exchange (connect)

1. Validate state: base64-decode → JSON-decode → verify structure → lookup in DB → delete from DB
2. Exchange authorization code for token: POST to `/auth/oauth2/token` with `grant_type=authorization_code`, Basic auth header
3. Save `OAuthInfo` entity (tenantId, accessToken, refreshToken, expiresIn, createdAt)
4. Get API key: `Proxy::getApiKeyWithToken($accessToken)` (tries POST then GET)
5. On 401: check token expiry (`time() >= createdAt + expiresIn`)
   - If expired: refresh token via POST `/auth/oauth2/token` with `grant_type=refresh_token`, retry getApiKey
   - If not expired: re-throw authentication exception
6. Delete `OAuthInfo` entity (cleanup)
7. Return API key string

### 3.3 OAuthConfiguration (abstract, platform must implement)

| Method | Purpose |
|--------|---------|
| `getClientId()` | OAuth client ID |
| `getClientSecret()` | OAuth client secret |
| `getRedirectUri()` | OAuth redirect URL |
| `getScopes()` | Array of OAuth scopes |
| `getDomain()` | Auth server domain |
| `getReturnUrl()` | Where user returns after auth |
| `getTenantId()` | Current tenant identifier |

### 3.4 Error Cases

- `InvalidOAuthStateException` — state validation failed (missing, malformed, or not in DB)
- `HttpAuthenticationException` — token exchange or API key retrieval failed
- `HttpCommunicationException` — network error

---

## 4. Module State Machine

```
┌─────────────────────┐
│  LOGIN_STATE        │  authorizationToken is empty
│  ('login')          │
└─────────┬───────────┘
          │ successful login/registration
          ▼
┌─────────────────────┐
│  ONBOARDING_STATE   │  token set, parcel OR warehouse is null
│  ('onBoarding')     │
│                     │
│  Sub-states:        │
│  WELCOME  — both null
│  OVERVIEW — at least one set
└─────────┬───────────┘
          │ both parcel AND warehouse set
          ▼
┌─────────────────────┐
│  SERVICES_STATE     │  fully configured
│  ('services')       │
└─────────────────────┘
```

### 4.1 Dashboard Status

| Flag | Condition |
|------|-----------|
| `isParcelSet` | `Configuration::getDefaultParcel() !== null` |
| `isWarehouseSet` | `Configuration::getDefaultWarehouse() !== null` |
| `isShippingMethodSet` | At least one activated shipping method exists |

---

## 5. Warehouse Configuration

### 5.1 Warehouse Fields

| Field | Required | Validation |
|-------|----------|------------|
| `alias` | Yes | — |
| `name` | Yes | — |
| `surname` | Yes | — |
| `country` | Yes | 2-letter ISO code, must be in warehouse countries |
| `postal_code` | Yes | Validated against Packlink API: `Proxy::getPostalCodes($country, $postalCode)` must return non-empty |
| `address` | Yes | — |
| `phone` | Yes | Valid phone format |
| `email` | Yes | Valid email format |
| `city` | No | Auto-parsed from postal_code if format is `"POSTAL - CITY"` |
| `company` | No | — |

### 5.2 Acceptance Criteria

- AC1: Postal code validated via API; invalid postal code returns validation error "Postal code is not correct."
- AC2: If `postal_code` contains `" - "`, split into postal code and city
- AC3: Country change (or first warehouse set) enqueues `UpdateShippingServicesBusinessTask`
- AC4: If warehouse not set and requested, creates empty one with user's country
- AC5: Warehouse countries are a **superset** of registration countries (from brand config)

---

## 6. Default Parcel

### 6.1 ParcelInfo Fields

| Field | Type | Validation |
|-------|------|------------|
| `weight` | float | Required, > 0, must be numeric (int or float) |
| `width` | int | Required, > 0, must be integer type |
| `length` | int | Required, > 0, must be integer type |
| `height` | int | Required, > 0, must be integer type |

**Default parcel** (used as fallback): weight=1, width=10, height=10, length=10

### 6.2 Acceptance Criteria

- AC1: String values for dimensions rejected ("validation.integer")
- AC2: Zero or negative values rejected ("validation.greaterThanZero")
- AC3: Any dimension change enqueues `UpdateShippingServicesBusinessTask` (detected via `array_diff`)
- AC4: First parcel set (old was null) also triggers sync

---

## 7. Shipping Method Management

### 7.1 Lifecycle

| Action | Precondition | Side Effect |
|--------|-------------|-------------|
| `activate(id)` | Method exists | Sets `activated=true`, calls `ShopShippingMethodService::add()`. First activation: adds backup method + analytics event |
| `deactivate(id)` | Method exists | Sets `activated=false`, calls `ShopShippingMethodService::delete()`. Last deactivation: removes backup method |
| `delete(method)` | Method NOT activated | Removes from repository. If activated: deactivates first |
| `save(method)` | Method exists | Persists. If activated: calls `ShopShippingMethodService::update()` |

### 7.2 Service Matching Criteria

When matching API services to existing methods:

| Criterion | Regular Service | Special Service (`EXCLUSIVE_FOR_PLUS` tag) |
|-----------|----------------|---------------------------------------------|
| `departureDropOff` | Must match | Must match |
| `destinationDropOff` | Must match | Must match |
| `national` | Must match | Must match |
| `expressDelivery` | Must match | Must match |
| `carrierName` | Must match | Combined: `"carrierName serviceName"` |
| `currency` | Must match | Must match |

### 7.3 Currency Configuration Validation (Multi-Store)

Before saving a method, for each system (store):
1. If method currency is in system currencies → valid
2. If pricing policy for this system uses `POLICY_FIXED_PRICE` → valid (currency-independent)
3. If pricing policy for this system uses other policy type → method currency must be in system currencies
4. If currency mismatch and using default → `fixedPrices['default']` must exist
5. If currency mismatch and system-specific → `fixedPrices[$systemId]` must exist

---

## 8. Shipping Price Policies

### 8.1 Range Types

| Constant | Value | Meaning |
|----------|-------|---------|
| `RANGE_PRICE` | 0 | Applies when cart total is in price range |
| `RANGE_WEIGHT` | 1 | Applies when package weight is in weight range |
| `RANGE_PRICE_AND_WEIGHT` | 2 | Applies when BOTH price AND weight ranges match |

### 8.2 Pricing Types

| Constant | Value | Formula |
|----------|-------|---------|
| `POLICY_PACKLINK` | 0 | Return Packlink API `basePrice` as-is |
| `POLICY_PACKLINK_ADJUST` | 1 | `basePrice ± (basePrice × changePercent / 100)`, rounded to 2 decimals |
| `POLICY_FIXED_PRICE` | 2 | Return `fixedPrice` (ignores Packlink price) |

### 8.3 Field Validation

| Field | Rule |
|-------|------|
| `rangeType` | Required |
| `fromPrice` | Required if range includes price; must be >= 0 |
| `toPrice` | Optional; if set, must be > `fromPrice` |
| `fromWeight` | Required if range includes weight; must be >= 0 |
| `toWeight` | Optional; if set, must be > `fromWeight` |
| `changePercent` | Required for ADJUST policy; must be > 0; if decrease: must be <= 99.99 |
| `fixedPrice` | Required for FIXED_PRICE policy; must be >= 0 |
| `systemId` | Scopes this policy to a specific shop/store |

---

## 9. Shipping Cost Calculation

### 9.1 Flow

1. Aggregate packages via `PackageTransformer::transform()` (sum weights, use default dimensions)
2. Get system info for `$systemId` (currencies, etc.)
3. Call `Proxy::getShippingServicesDeliveryDetails()` with origin/destination/packages
4. Match API services to local `ShippingMethod` entities
5. For each matched method: evaluate pricing policies
6. Return `array(methodId => cost)`

### 9.2 Policy Matching

- Policies evaluated **in order** — first match wins
- Policy `systemId` must match current system
- **Price range**: `fromPrice <= totalPrice` AND (`toPrice` empty OR `totalPrice <= toPrice`)
- **Weight range**: `fromWeight <= totalWeight` AND (`toWeight` empty OR `totalWeight <= toWeight`)
- **Both**: price AND weight must both match

### 9.3 Fallback Chain

| Scenario | Behavior |
|----------|----------|
| No policy matches + `usePacklinkPriceIfNotInRange=true` | Use Packlink API `basePrice` |
| No policy matches + `usePacklinkPriceIfNotInRange=false` | Method unavailable (returns false/0) |
| API error (HTTP status != 400) | Use stored default prices from method's `shippingServices` |
| API error (HTTP status == 400) | Re-throw (invalid request) |
| Currency mismatch (method currency not in system currencies) | Use `fixedPrices[$systemId]` or `fixedPrices['default']` |
| Postal code transform fails | Retry with original postal code; if still fails, empty result |

### 9.4 Package Transformation

| Input | Result |
|-------|--------|
| Empty packages | Default parcel: 1kg, 10x10x10cm |
| Single package | Use its dimensions (fill missing from default) |
| Multiple packages | Sum all weights; use default dimensions for width/height/length |

---

## 10. Postal Code Transformation

Applied to destination postal codes before API calls:

| Country | Rule |
|---------|------|
| AE | Always returns `'1'` |
| LV | Strips `'LV-'` prefix if present |
| GB, IM, JE, GG | Formats: `XX XXX`, `XXX XXX`, `XXXX XXX` |
| GR | Format: `XXX XX` |
| NL | Format: `XXXX XX` or `XXXX` |
| PT | Format: `XXXX-XXX` or `XXXX` |
| US | Extracts first 5 digits from `XXXXX-XXXX` or `XXXXX` |

Throws `InvalidArgumentException` if postal code doesn't match expected format for supported countries. Unsupported countries pass through unchanged.

---

## 11. Shipping Services Sync

### 11.1 Execution Conditions

Task only executes if:
- User info is set (`Configuration::getUserInfo() !== null`)
- AND (default warehouse is set OR user's country is in warehouse countries)

### 11.2 Remote Service Retrieval

For each supported destination country, queries API with:
- **From**: warehouse country + postal code (or user country if no warehouse)
- **To**: destination country + postal code
- **Package**: default parcel dimensions

### 11.3 Sync Logic

1. Separate API results into **regular** and **special** (tag `EXCLUSIVE_FOR_PLUS`) services
2. For each existing method: find matching API services, update method, remove matched services from pool
3. For remaining unmatched API services: create new `ShippingMethod`

### 11.4 Orchestrator Deduplication

Before enqueueing, checks latest task status. Skips if status is:
`CREATED`, `SCHEDULED`, `PENDING`, `RUNNING`, `queued`, `in_progress`

### 11.5 Triggers

| Event | Source |
|-------|--------|
| Successful login | `UserAccountService::login()` |
| Warehouse country change | `WarehouseService::updateWarehouseData()` |
| Parcel dimension change | `DefaultParcelController::setDefaultParcel()` |
| Manual refresh | `ManualRefreshController::enqueueUpdateTask()` |
| Weekly schedule | `SchedulerInterface` (random day/hour/minute) |
| Auto-configuration | `AutoConfigurationController::start(enqueueTask=true)` |

### 11.6 Status Tracking

`UpdateShippingServiceTaskStatus` entity per context:

| Status | Meaning |
|--------|---------|
| `CREATED` | Task just enqueued |
| `IN_PROGRESS` / `RUNNING` | Task executing |
| `COMPLETED` | Sync finished successfully |
| `FAILED` | Sync failed (error message stored) |

---

## 12. Shipment Draft Creation

### 12.1 Trigger

`ShipmentDraftService::enqueueCreateShipmentDraftTask($orderId, $isDelayed, $delayInterval)`
- Skips if draft status is already `PROCESSING` or `COMPLETED`
- If `$isDelayed=true`: schedules task `$delayInterval * 60` seconds later (default 5 minutes)
- If `$isDelayed=false`: enqueues immediately

### 12.2 Draft Status States

```
NOT_QUEUED → DELAYED → PROCESSING → COMPLETED
                                   → FAILED (error message stored)
NOT_QUEUED → PROCESSING → COMPLETED
                        → FAILED
```

All draft statuses map to shipment status `STATUS_PENDING`.

### 12.3 Draft DTO Field Mapping

**Sender (from warehouse):**

| Draft Field | Source |
|-------------|--------|
| `from.country` | `warehouse.country` |
| `from.zipCode` | `warehouse.postalCode` (transformed) |
| `from.name` | `warehouse.name` |
| `from.surname` | `warehouse.surname` |
| `from.email` | `warehouse.email` |
| `from.city` | `warehouse.city` |
| `from.company` | `warehouse.company` |
| `from.phone` | `warehouse.phone` |
| `from.street1` | `warehouse.address` |

**Recipient (from order shipping address):**

| Draft Field | Source |
|-------------|--------|
| `to.country` | `shippingAddress.country` (ISO2) |
| `to.zipCode` | `shippingAddress.zipCode` (postal-code-transformed) |
| `to.name` | `shippingAddress.name` |
| `to.surname` | `shippingAddress.surname` |
| `to.email` | `shippingAddress.email` |
| `to.city` | `shippingAddress.city` |
| `to.company` | `shippingAddress.company` |
| `to.phone` | `shippingAddress.phone` |
| `to.street1` | `shippingAddress.street1` |
| `to.street2` | `shippingAddress.street2` |

**Packages & Content:**

| Draft Field | Source |
|-------------|--------|
| `packages[]` | Aggregated via `PackageTransformer` |
| `content[]` | `"quantity title"` per item |
| `contentValue` | `order.getTotalPrice()` |
| `contentValueCurrency` | `order.getCurrency()` (ISO3) |
| `contentSecondHand` | Always `false` |
| `priority` | `order.isHighPriority()` |
| `shipmentCustomReference` | `order.getOrderNumber()` (max 50 chars) |
| `source` | `Configuration::getDraftSource()` |
| `platformCountry` | `user.country` (ISO2) |

**Service details** (if shipping method selected):

| Draft Field | Source |
|-------------|--------|
| `serviceName` | `shippingMethod.title` |
| `carrierName` | `shippingMethod.carrierName` |
| `serviceId` | From cheapest service for route |
| `dropOffPointId` | `order.getShippingDropOffId()` |

### 12.4 Weight Unit Conversion

| Unit | Conversion to kg |
|------|-----------------|
| `kg` | unchanged |
| `g` | `÷ 1000` |
| `oz` | `× 0.02834952` |
| `lbs` | `× 0.45359237` |

### 12.5 Task Progress

`SendDraftBusinessTask` yields: 5 → 10 → 20 → 40 → 50 → 70 → 80 → 90 → 100

### 12.6 Acceptance Criteria

- AC1: Order must have at least one item (throws `EmptyOrderException`)
- AC2: Duplicate enqueue for same orderId is prevented (checks PROCESSING/COMPLETED status)
- AC3: Delayed task fires after configured interval (default 5 minutes)
- AC4: On failure: draft status set to FAILED with error message
- AC5: After successful draft: fetches shipment data and updates details (price, status, customs)

---

## 13. Shipment Status & Tracking

### 13.1 Packlink API Status → System Status

| Packlink API Status | System Status |
|---------------------|---------------|
| `AWAITING_COMPLETION`, `READY_TO_PURCHASE` | `pending` |
| `PURCHASE_SUCCESS`, `CARRIER_PENDING`, `RETRY`, `CARRIER_KO`, `LABELS_KO`, `INTEGRATION_KO` | `processing` |
| `READY_TO_PRINT`, `READY_FOR_COLLECTION`, `COMPLETED`, `CARRIER_OK` | `readyForShipping` |
| `IN_TRANSIT` | `inTransit` |
| `OUT_FOR_DELIVERY` | `outForDelivery` |
| `DELIVERED`, `RETURNED_TO_SENDER` | `delivered` |
| `CANCELED` | `cancelled` |
| `INCIDENT` | `incident` |
| (any unknown) | `pending` |

### 13.2 Feature Availability by Status

| Feature | Available When Status Is |
|---------|------------------------|
| **Fetch labels** | `readyForShipping`, `inTransit`, `outForDelivery`, `delivered` |
| **Update tracking** | `processing`, `readyForShipping`, `inTransit`, `outForDelivery` |

### 13.3 Status Update Process (webhook-driven)

1. Set shipping price and currency
2. Map and set shipping status
3. Call `ShopOrderService::updateShipmentStatus()` (platform-specific)
4. If tracking updatable: fetch tracking info from API, update order
5. If customs invoice ID provided: store on shipment details

---

## 14. Labels

### 14.1 ShipmentLabel Model

| Field | Type | Default |
|-------|------|---------|
| `link` | string | — (URL to PDF) |
| `printed` | bool | `false` |
| `createTime` | DateTime | Current time |

### 14.2 Label Fetch-and-Cache

Pattern used across all integrations:
1. Check `OrderShipmentDetails::getShipmentLabels()`
2. If empty: `OrderService::getShipmentLabels($reference)` → calls `Proxy::getLabels($reference)`
3. Persist via `OrderShipmentDetailsService::setLabelsByReference()`
4. Labels are arrays of URL strings from API, wrapped in `ShipmentLabel` objects

### 14.3 Print Tracking

`OrderShipmentDetailsService::markLabelPrinted($reference, $link)`:
- Matches label by exact `link` string
- Sets `printed = true`
- Persists

---i want 

## 15. Customs

### 15.1 International Shipment Detection

`CustomsService::isShipmentInternational($toCountry, $toPostalCode)`:
- Sends `CustomsUnionsSearchRequest` with warehouse origin + destination
- Calls `Proxy::getCustomsByPostalCode()`
- **International if result is empty** (countries not in same customs union)

### 15.2 Customs Invoice Preconditions

`shouldCreateCustoms()` requires:
- Shipment is international
- Warehouse has: city, address, country, phone, postalCode, AND (name OR surname)
- Customs mapping is configured

### 15.3 CustomsInvoice Structure

**Sender** (from warehouse + user info):

| Field | Value |
|-------|-------|
| `userType` | `'company'` if user.customerType is company, else `'private_person'` |
| `fullName` | `warehouse.name + ' ' + warehouse.surname` |
| `taxId` | If private: `mapping.defaultSenderTaxId`; if company: empty |
| `companyName` | If company: `warehouse.company`; else empty |
| `vatNumber` | If company: `mapping.defaultSenderTaxId`; else empty |
| `address` | `warehouse.address` |
| `postalCode` | `warehouse.postalCode` |
| `city` | `warehouse.city` |
| `country` | `warehouse.country` (ISO2) |
| `phoneNumber` | `warehouse.phone` |

**Receiver** (from order shipping address):

| Field | Value |
|-------|-------|
| `userType` | `mapping.defaultReceiverUserType` |
| `fullName` | `shippingAddress.name + ' ' + shippingAddress.surname` |
| `taxId` | If private: `order.getTaxId()` or `mapping.defaultReceiverTaxId` |
| `companyName` | If company: `shippingAddress.company` |
| `vatNumber` | If company: `order.getVatNumber()` or `mapping.defaultReceiverTaxId` |
| other fields | From shipping address |

**Inventory Content** (per order item):

| Field | Value |
|-------|-------|
| `tariffNumber` | `item.getTariffNumber()` or `mapping.defaultTariffNumber` (regex: `/^[0-9]{6,8}$/`) |
| `description` | `item.getTitle()` |
| `countryOfOrigin` | `item.getCountryOfOrigin()` or `mapping.defaultCountry` |
| `itemValue.currency` | `order.getCurrency()` |
| `itemValue.value` | `item.getPrice()` (unit price) |
| `itemWeight` | `item.getWeight()` |
| `quantity` | `item.getQuantity()` |

**Shipment Details:**
- `parcelsSize`: always `1`
- `parcelsWeight`: `order.getTotalWeight()`
- `cost.currency`: `order.getCurrency()`
- `cost.value`: `order.getTotalPrice()`

**Signature:** `fullName` = warehouse name + surname, `city` = warehouse city

### 15.4 CustomsMapping Fields

| Field | Required | Validation |
|-------|----------|------------|
| `defaultReason` | Yes | — |
| `defaultSenderTaxId` | Yes | — |
| `defaultReceiverUserType` | Yes | `'company'` or `'private_person'` |
| `defaultReceiverTaxId` | No | — |
| `defaultTariffNumber` | No | 6-8 digits: `/^[0-9]{6,8}$/` |
| `defaultCountry` | No | ISO2 code |

### 15.5 Acceptance Criteria

- AC1: Customs invoice only created for international shipments (empty customs union result)
- AC2: Incomplete warehouse (missing required fields) skips customs creation
- AC3: Items without tariff number use `mapping.defaultTariffNumber`
- AC4: Items without country of origin use `mapping.defaultCountry`
- AC5: On customs API failure: logs warning, continues without customs (draft still sent)
- AC6: Customs invoice ID stored on `OrderShipmentDetails` for later download

---

## 16. Cash on Delivery

### 16.1 Configuration

Per-system (scoped by `systemId`):

| Field | Type |
|-------|------|
| `enabled` | bool |
| `active` | bool |
| `account.accountHolderName` | string |
| `account.iban` | string |
| `account.offlinePaymentMethod` | string (payment method ID) |
| `account.cashOnDeliveryFee` | float or null |

### 16.2 Fee Calculation

```
calculated = round(orderTotal × (percentage / 100), 2)
result = max(calculated, minFee)
```

### 16.3 COD Inclusion in Draft

COD details added to draft only if ALL conditions met:
1. `CashOnDeliveryConfig` exists for current system
2. `config.isActive() === true`
3. `config.getAccount()` is not null
4. `config.getAccount().getOfflinePaymentMethod() === order.getPaymentId()`

If included: `CashOnDeliveryDetails(totalAmount, accountHolderName, iban)`

---

## 17. Webhooks

### 17.1 Event Catalog

| Event | Valid | Handled |
|-------|-------|---------|
| `shipment.carrier.success` | Yes | Yes |
| `shipment.carrier.fail` | Yes | No |
| `shipment.label.ready` | Yes | Yes |
| `shipment.label.fail` | Yes | No |
| `shipment.tracking.update` | Yes | Yes |
| `shipment.delivered` | Yes | Yes |
| `shipment.carrier.delivered` | Yes | Yes |

### 17.2 Payload Validation

Required fields: `datetime` (any value), `data` (any value), `event` (must be in valid events list)

### 17.3 Processing Flow

1. JSON-decode input
2. Validate payload structure
3. Verify `Configuration::getAuthorizationToken()` is non-empty (skip if not authenticated)
4. Check event is in handled events whitelist
5. `Proxy::getShipment($data->shipment_reference)` — if null, log warning and return
6. `OrderService::updateShipmentData($shipment)` — updates price, status, tracking, customs

### 17.4 Error Handling

| Error | Behavior |
|-------|----------|
| HTTP 429 (rate limit) | Re-thrown (not caught) |
| Other `HttpBaseException` | Logged as warning, continues |
| `OrderShipmentDetailsNotFound` | Logged as warning, continues |
| All other exceptions | Caught and logged |
| Always returns `true` | Webhook acknowledged regardless of processing result |

---

## 18. Analytics, Status Mapping, Auto-Test & Platform Contract

### 18.1 Analytics

**Setup event** (`EVENT_SETUP = 'setup'`):
- Sent when ALL conditions met:
  - `isSetupFinished() === false`
  - Default parcel is set
  - Default warehouse is set
  - Exactly 1 active shipping method
- After sending: `setSetupFinished(true)` (sent only once)

**Other services disabled** (`disable_carriers`): sent manually via controller

### 18.2 Order Status Mapping

8 Packlink statuses mappable to shop-specific status IDs:

`pending`, `processing`, `readyForShipping`, `inTransit`, `delivered`, `cancelled`, `incident`, `outForDelivery`

Default: all mapped to empty string (unmapped). Platform stores shop-side status IDs as values.

### 18.3 Auto-Test

1. Sets auto-test mode (swaps logger to in-memory `AutoTestLogger`)
2. Enqueues `AutoTestBusinessTask`
3. Polls status: if PENDING after 30 seconds → timeout with error "Task could not be started."
4. Collects logs from `AutoTestLogger`
5. Returns `{finished: bool, error: string, logs: array}`
6. Stop: resets auto-test mode, restores shop logger

### 18.4 Manual Refresh

`ManualRefreshController::enqueueUpdateTask()`:
- Enqueues `UpdateShippingServicesBusinessTask` via orchestrator
- Returns `{status: 'success'|'error', message: string}`

### 18.5 Platform Integration Contract

**Abstract methods (Configuration):**

| Method | Returns |
|--------|---------|
| `getWebHookUrl()` | Webhook callback URL |
| `getDraftSource()` | Order draft source identifier |
| `getModuleVersion()` | Module version string |
| `getECommerceName()` | Platform name (as Packlink knows it) |
| `getECommerceVersion()` | Platform version string |
| `getCurrentSystemId()` | Current shop/store identifier |

**Required service registrations** (via `ServiceRegister`):
- `Configuration` (concrete)
- `HttpClient`
- `ShopOrderService` (updateShipmentStatus, updateTrackingInfo)
- `ShopShippingMethodService` (add, update, delete methods in shop)
- `TaskRunnerWakeup` (HTTP trigger for async execution)
- `SchedulerInterface` (scheduleWeekly, scheduleDaily, scheduleHourly)
- `ShopLoggerAdapter` (logMessage)
- `Serializer` (NativeSerializer or JsonSerializer)
- `OAuthConfiguration` (if OAuth supported)

**Required repository registrations** (via `RepositoryRegistry`):
QueueItem, ConfigEntity, Process, Schedule, ShippingMethod, OrderShipmentDetails, OrderSendDraftTaskMap, LogData, OAuthState, OAuthInfo, CashOnDelivery, UpdateShippingServiceTaskStatus
