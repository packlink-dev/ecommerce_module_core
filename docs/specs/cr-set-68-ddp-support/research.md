# Research: CR-SET-68 — DDP Support (integration-core)

> **Feature:** cr-set-68-ddp-support
> **Date:** 2026-07-29
> **Requirement source:** `CR-68-DDP-Support.pdf` (v1.2, Jul 14 2026) — text + mockups extracted
> **Scope hint from invocation:** extract the **core** (platform-agnostic) behaviour; platform CRs (Shopify/WooCommerce/PrestaShop) carry the checkout/orders-page rendering.

---

## 1. Requirement summary (from the PDF)

Packlink introduces **Delivered Duty Paid (DDP)**: import duties/taxes paid up front at
shipment creation instead of collected from the recipient. Initial release: **UPS only**.
PrestaShop/WooCommerce delivery depends on **CR-SET-66 customs support** (customs
configuration is a prerequisite).

Behaviours (per section, with mockups):

1. **Services overview page** — based on **new fields in the `GET /v1/services` response**,
   international services that support DDP (optionally or mandatorily) show a
   **"Customs and duties paid"** badge next to the service title (mockup p7).
2. **Service details (edit-service) page** —
   - If customs information is **not configured**, every DDP-supported service shows an info
     banner: *"In order to offer this service to your customers, please configure customs
     information within this module."* (p8).
   - For **optional-DDP** services, a **"Duties cost at checkout"** select with values:
     *No duties charged on checkout* / *Offer duties cost on checkout optionally* /
     *Enforce duties cost on checkout* (p8).
   - For **mandatory-DDP** services this select is **not shown**; instead a banner: *"This is a
     service that has mandatory duties cost included at checkout. Only activate this service if
     you consent with offering this service with duties cost included on the checkout."* (p9).
   - **"Adjust DDP cost"** section (shown when duties are offered/enforced): adjustment type
     select (*Fixed adjustment* / *Percentage adjustment*) + adjustment amount input (p9).
3. **Checkout flow** — when shipping internationally the module makes **one additional
   Packlink API call** to retrieve the DDP cost for the applicable services (external service;
   **API reference to be provided by Packlink** — not in the PDF).
   - Optional DDP, not enforced → customer sees **two options** for the service: one with DDP
     included ("… - Delivery Duty Paid", price + DDP) and one without. When the DDP option is
     selected the DDP cost appears as a **separate line item** ("Delivery Duty Paid") in the
     order summary (p10 WooCommerce, p11 PrestaShop mockups).
   - Mandatory DDP or merchant-enforced → **only** the DDP option is offered.
4. **Orders page** — the order-details popup shows **"DDP cost"** (e.g. `12,00 €`) for
   shipments **created with DDP** (p12 WooCommerce, p13 PrestaShop mockups, next to
   "Packlink shipping price").

## 2. What was read

- `CR-68-DDP-Support.pdf` (all 12 pages; 13 mockup images extracted to `/tmp/cr68/`).
- `docs/change-requests/2026-06-cr66-customs-support/spec.md` + `design.md` (dependency CR).
- Three parallel read-only research agents over the core:
  - services sync/model axis, checkout-cost/draft/order axis, shared-UI/customs-check axis.

Key files identified (all paths relative to repo root):

| Area | Files |
|---|---|
| Services API DTOs | `src/BusinessLogic/Http/DTO/ShippingService.php`, `Http/DTO/ShippingServiceDetails.php` (price at `fromArray()` L188-223; nested `price`, `cash_on_delivery` objects) |
| Proxy | `src/BusinessLogic/Http/Proxy.php` — `getShippingServicesDeliveryDetails()` L399-426 (`GET v1/services?…`); CR-66 customs endpoints L569-614 as the pattern for a new endpoint |
| Shipping method model | `src/BusinessLogic/ShippingMethod/Models/ShippingMethod.php` (fields L26-47, `getConfig()` L255, `inflate/toArray` L184-247), `Models/ShippingService.php` (business model; `fromServiceDetails()` L133), `Models/CashOnDeliveryConfig.php` (nested-config precedent), `Models/ShippingPricePolicy.php` (fixed/percent adjustment precedent: `POLICY_PACKLINK_ADJUST`, `changePercent`, `fixedPrice`) |
| Sync task | `src/BusinessLogic/Tasks/BusinessTasks/UpdateShippingServicesBusinessTask.php` (`syncServices`, `serviceBelongsToMethod` L374); `ShippingMethodService.php` (`setShippingMethodDetails()` L567-592, `setShippingService()` L600-622) |
| Controllers/DTOs | `src/BusinessLogic/Controllers/ShippingMethodController.php` (`save()` L162, `updateModelData()` L288, `transformShippingMethodModelToDto()` L253), `Controllers/DTO/ShippingMethodConfiguration.php`, `DTO/ShippingMethodResponse.php` |
| Checkout costs | `src/BusinessLogic/ShippingMethod/ShippingCostCalculator.php` — `getShippingCost()` L47, `getShippingCosts()` L86 (returns `methodId => cost`), `getCheapestShippingService()` L146, policy application `calculateCost()` L522-538 |
| Draft/order | `src/BusinessLogic/Http/DTO/Draft.php` (`hasCustoms` L156, `customs` L160), `Order/OrderService.php` (`convertOrderToDraftDto()` L331, `updateShipmentData()` L132), `Tasks/BusinessTasks/SendDraftBusinessTask.php` (`tryCreateCustomsInvoice()` L189) |
| Shipment details persistence | `src/BusinessLogic/OrderShipmentDetails/Models/OrderShipmentDetails.php` (`shippingCost`, `currency`, `customsInvoiceId` — CR-66 precedent L508-519), `OrderShipmentDetailsService.php` (`setShippingPrice()` L188, `updateShipmentCustomsData()` L151) |
| Customs check | `src/BusinessLogic/Customs/CustomsMappingService.php` — `getCustomsMappings()` L61 **always returns an object** (empty mapping when unset); `Configuration::getCustomsMappings()` returns null when never saved |
| Shared UI | `Resources/templates/my-shipping-services.html`, `shipping-services-list.html`, `shipping-services-table.html`, `edit-shipping-service.html`, `pricing-policy-modal.html`; `Resources/js/MyShippingServicesController.js`, `ShippingServicesRenderer.js`, `EditServiceController.js` |
| Banners/styles | `.pl-alert` (`scss/ui-controls.scss` L405-433), `.pl-info-box` (`templates/info-box.html`, `scss/subscription.scss` L58-86 — matches the blue info banner in mockups), yellow warning variant does **not** exist yet; badges/tags styles do **not** exist yet |
| Translations | `src/BusinessLogic/Resources/countries/{en,de,es,fr}.json` (`shippingServices` group at en.json:437, `customs` group at :370); templates use `{$group.key}`, JS uses `Packlink.translationService.translate()` |
| Tests | `tests/BusinessLogic/ShippingMethod/ShippingMethodServiceCostsTest.php`, `Tasks/UpdateShippingServicesTaskTest.php`, `Tasks/SendDraftTaskTest.php`, `Order/OrderShipmentDetailsServiceTest.php`; API fixtures under `tests/BusinessLogic/Common/ApiResponses/ShippingServices/` |

## 3. What exists for reuse

- **Nested per-service config precedent:** `CashOnDeliveryConfig` on the `ShippingService`
  business model — a DDP support descriptor (`offered`/`mandatory` etc.) can follow the same
  shape (`fromArray`/`toArray`, parsed in `ShippingService::fromServiceDetails()`).
- **Fixed/percent adjustment precedent:** `ShippingPricePolicy` (`POLICY_PACKLINK_ADJUST`
  percent, `POLICY_FIXED_PRICE`) and `ShippingCostCalculator::calculateCost()` — the DDP
  cost adjustment (fixed amount / percentage of DDP cost) mirrors this, but is a single
  per-method setting, not a range-based policy list.
- **Merchant-config round trip:** `ShippingMethodConfiguration` → `updateModelData()` →
  `ShippingMethod` → `transformShippingMethodModelToDto()` → `ShippingMethodResponse` — new
  DDP fields ride this existing path.
- **Sync path:** `UpdateShippingServicesBusinessTask` + `ShippingMethodService::
  setShippingMethodDetails()` already copy API fields per sync — new DDP support flags flow
  through the same copy.
- **New Proxy endpoint pattern:** CR-66 customs endpoints (`sendCustomsInvoice`, etc.) show
  the request-DTO + `call()` pattern for the new DDP-cost endpoint.
- **Persist-on-shipment precedent:** `customsInvoiceId` on `OrderShipmentDetails` +
  `OrderShipmentDetailsService::updateShipmentCustomsData()` (CR-66) — `ddpCost` follows the
  same recipe for the orders-page popup.
- **Banner markup:** `.pl-info-box` (blue info) matches the customs-not-configured banner
  mockup; the mandatory-DDP banner in the mockup is **yellow/warning** — needs a small SCSS
  variant (compile via `cssCompile.php`; **trap:** verify no hand-edited `app.css` rules are
  lost — LEARNINGS.md 2026-07-17).
- **Conditional form sections:** `EditServiceController.js` already shows/hides sections
  (`pl-hidden`, tax-class/countries sections) — the DDP behavior select / adjust section /
  banners reuse this mechanism.
- **Customs-configured check:** `Configuration::getCustomsMappings()` returns `null` when
  never saved — usable as the "customs not configured" signal (note:
  `CustomsMappingService::getCustomsMappings()` masks null with an empty object; the check
  likely belongs at config level or needs a dedicated service method).

## 4. Constraints

- **PHP 7.0**, `array()` syntax, no nullable/void/typed properties (`docs/coding-standard.md`).
- **Platform agnostic:** core exposes building blocks; checkout rendering (two shipping
  options, line item) and orders-popup rendering are platform scope. Core must not know
  platform checkout objects.
- **Breaking changes to abstract contracts** must be flagged (CR-66 precedent:
  `getMappingFieldsOptions()` rename) — platform modules implement them.
- **PHPUnit 4.8** test style, `@before`/`@after`, memory repositories, `TestHttpClient` +
  JSON fixtures.
- The DDP cost comes from **an external service Packlink also uses on its own portal**; the
  PDF explicitly says Packlink will provide the API reference — **the endpoint contract is
  not in the requirement document**.
- Checkout performance is called out as a concern (one extra API call per rate request).
- v1 is **UPS only**, but detection must be driven by the API's DDP fields, not hardcoded
  carrier names.
- Internal docs (`docs/`, `CLAUDE.md`, …) never go to `origin`; delivery via
  `docs/tools/publish-to-origin.sh`.

## 5. Open questions (feed the spec interview)

1. **DDP-cost API contract** — endpoint, method, request/response shape for retrieving the
   DDP cost (per service? batch?). Not in the PDF ("Packlink will provide the API
   reference"). Also: the exact **new field names** on `GET /v1/services` marking optional /
   mandatory DDP support. Do we have the API reference, or should the spec define
   placeholder DTOs/endpoints to be confirmed?
2. **Core/platform boundary at checkout** — should core expose a new calculator entry point
   (e.g. DDP costs per method id alongside `getShippingCosts()`), leaving option-splitting
   (two checkout choices) to platforms? Or return a richer structure per method
   (base cost + ddp cost + behavior flags)?
3. **Draft/shipment linkage** — how does Packlink know a shipment was created *with DDP*?
   Is there a draft field (like `has_customs`) to send, or is DDP implied by the service
   selected? Where does the persisted orders-page `ddpCost` value come from — the checkout
   selection (platform hands it to core with the Order) or the shipment response?
4. **Adjustment semantics** — fixed/percentage adjustment: increase only, or both
   directions? Applied to the DDP cost shown at checkout only, or also to what is persisted?
   Rounding rules (2 decimals like pricing policies?).
5. **Duties-behavior default** — default select value ("No duties charged on checkout"?)
   and what happens when customs is not configured but the merchant enforces DDP (block
   save? just the banner?).
6. **Badge placement/state** — badge on overview for *all* DDP-capable services regardless
   of merchant behavior config? (Mockup shows it per service row incl. a domestic UPS row —
   p7 shows badge on a "Domestic" row too, contradicting "international shipping services";
   confirm.)
7. **Caching / performance** — should core cache DDP cost lookups (the PDF raises checkout
   performance as the reason options were weighed)? Any TTL guidance?
8. **Mandatory-DDP price display** — for mandatory services, is the DDP cost folded into
   the shipping price shown (banner says "included in the shipping price") and does the
   separate line item still appear?
9. **Multistore** — pricing has per-system (`systemId`) scoping (fixedPrices,
   systemDefaults, policy systemId). Does the DDP behavior/adjustment need per-system
   scoping too, or single-store like customs mappings?
10. **CoD interplay** — services with cash-on-delivery config: any interaction with DDP
    (both add-ons on the same service)?

## 6. Non-goals (platform scope, per PDF structure)

- Checkout rendering: duplicated shipping options, DDP line item, totals (WooCommerce /
  PrestaShop / Shopify checkout surfaces).
- Orders-page popup markup (platform order-details widgets); core only persists/exposes the
  DDP cost.
- Install/migration defaults and platform settings storage.
