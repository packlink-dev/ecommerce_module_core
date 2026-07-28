# Tasks: CR-SET-66 — Customs Support (integration-core)

> **Version:** 1.0
> **Date:** 2026-06-25
> **Status:** Draft
> **Layer:** integration-core
> **Spec:** [spec.md](spec.md)
> **Design:** [design.md](design.md)

---

The customs back-end already exists (CR-SET-42). T1–T5 close the **test-coverage** gaps
(G1–G5); **T6** is the common-UI feature work (G6) — extending the customs settings page with
the additional mapping selects shown in the mockups. All tasks follow
`docs/coding-standard.md` and the test patterns in `design.md` §4.

## Task 1 — `CustomsMapping` validation tests (G1)

**Effort:** Small **Dependencies:** None

**Create** `tests/BusinessLogic/Customs/CustomsMappingTest.php`
- Register `CustomsMapping::CLASS_KEY` + `ValidationError::CLASS_KEY` via `TestFrontDtoFactory`.
- Assert required-field failures: missing `default_reason` / `default_sender_tax_id` /
  `default_receiver_user_type` → `FrontDtoValidationException`.
- Assert tariff regex: `123` and `123456789` fail; `123456` and `12345678` pass.
- Assert `fromArray()` → `toArray()` round-trip preserves all seven fields.

## Task 2 — `CustomsService` tests (G2)

**Effort:** Medium **Dependencies:** None

**Create** `tests/BusinessLogic/Customs/CustomsServiceTest.php`
- Use `TestHttpClient` + fixtures `searchResult.json` / `emptySearchResult.json`.
- `isShipmentInternational()` → true for a customs-union-crossing result, false for empty.
- `shouldCreateCustoms()` → false when the warehouse is incomplete (missing phone/address/etc.),
  true when complete **and** international.
- `sendCustomsInvoice()` builds a `CustomsInvoice` whose request body matches the schema
  (sender/receiver user types, inventory tariff/country fallback to mapping defaults,
  shipment details, signature) and returns the id parsed from `createCustomsResult.json`.
  Assert the captured `TestHttpClient` request payload.

## Task 3 — Extend `CustomsControllerTest` (G3)

**Effort:** Small **Dependencies:** None

**Modify** `tests/BusinessLogic/Controllers/CustomsControllerTest.php`
- `testGetAllCountries()` → returns the `CountryCodes::$countryCodes` map.
- `testGetReceiverTaxIdOptions()` → returns the `TaxIdOption[]` from `MockCustomsMappingService`.
- `testSaveInvalidMappingThrows()` → invalid tariff/required field surfaces
  `FrontDtoValidationException` and nothing is persisted.

## Task 4 — Confirm/extend `CUSTOMS_INVOICE` document coverage (G4)

**Effort:** Small **Dependencies:** None

**Modify** `tests/BusinessLogic/ShipmentDocument/ShipmentDocumentServiceTest.php`
- With `OrderShipmentDetails.customsInvoiceId` set and `Proxy.getCustomsInvoiceDownloadUrl`
  stubbed (`downloadUrl.json`), assert `getDocumentsForOrder()` returns a `CUSTOMS_INVOICE`
  document with the resolved link.
- Assert that an API failure (no fixture / error) yields **no** customs document and is logged
  (matches `collectCustomsInvoice()` swallow-and-log behaviour).

## Task 5 — Draft→shipment customs propagation test (G5)

**Effort:** Small **Dependencies:** None

**Add** (or extend the relevant `SendDraftBusinessTask` / `OrderService` test)
- For an international order: `Draft::$hasCustoms === true`, `Draft.customs.customs_invoice_id`
  populated, and after `OrderService::updateShipmentData($shipment, $customsId)` the
  `OrderShipmentDetails.customsInvoiceId` is persisted.

## Task 6 — Customs page mapping selects + model + generic rendering (G6 / UC-2)

**Effort:** Medium **Dependencies:** None (UI + code)

Bring the common customs settings page up to the mockups (`requirements/images/packlink_export_img005.png`,
`…_img008.png`): render mapping selects for **Product HS code field** and (platform-supplied)
**Company VAT**, alongside the existing customer/receiver tax-id mapping, driven generically by
platform-provided option lists (see `design.md` §3.1).
- **Modify** `Customs/Models/CustomsMapping.php` — add optional mapping fields (e.g.
  `mapping_tariff_number`, and a company-VAT mapping field) to `$fields`, `fromArray`, `toArray`;
  keep them out of `$requiredFields`.
- **Modify** `Resources/templates/customs.html` + `Resources/js/CustomsController.js` — render a
  mapping `<select>` per platform-supplied field definition (label + options), extending
  `modelFields`; reuse the existing `getCustomData` option-loading pattern. Do not hardcode
  platform field names.
- **Modify** `Customs/CustomsService.php` — consume the new mappings when assembling inventory
  (HS code) and receiver (tax id / company VAT) data, with defaults as fallback.
- **Add** the new translation keys to `Resources/countries/*.json`; recompile SCSS only if styles
  change (`php cssCompile.php`).
- Add tests mirroring T1 (model round-trip/validation) and T2 (service consumes mappings).

> Boundary note: the *set* of optional mapping fields and their options is platform-driven.
> Core renders what the platform supplies; PrestaShop/WooCommerce wiring of the actual product/
> customer attributes is tracked in the platform CRs, not here.

---

## Task Dependency Graph

```
T1 (mapping validation) ─┐
T2 (CustomsService)      ─┤
T3 (controller)          ─┼─ test coverage, independent, parallelizable
T4 (document path)       ─┤
T5 (draft→shipment)      ─┘

T6 (customs page mapping UI + model + service) ── independent feature task
     └─ its own model/service tests extend T1/T2
```

## Files Changed Per Task

| Task | New Files | Modified Files |
|------|-----------|----------------|
| T1 | `tests/BusinessLogic/Customs/CustomsMappingTest.php` | — |
| T2 | `tests/BusinessLogic/Customs/CustomsServiceTest.php` | — |
| T3 | — | `tests/BusinessLogic/Controllers/CustomsControllerTest.php` |
| T4 | — | `tests/BusinessLogic/ShipmentDocument/ShipmentDocumentServiceTest.php` |
| T5 | (maybe) a focused task/service test | existing `SendDraftBusinessTask` / `OrderService` test |
| T6 | mapping model/service tests | `Customs/Models/CustomsMapping.php`, `Resources/templates/customs.html`, `Resources/js/CustomsController.js`, `Customs/CustomsService.php`, `Resources/countries/*.json` |

## Verification

```bash
# Customs unit + controller tests
php vendor/bin/phpunit --configuration phpunit.xml tests/BusinessLogic/Customs
php vendor/bin/phpunit --configuration phpunit.xml tests/BusinessLogic/Controllers/CustomsControllerTest.php

# Document exposure
php vendor/bin/phpunit --configuration phpunit.xml tests/BusinessLogic/ShipmentDocument
```

- All targeted suites green.
- Confirm green across PHP 7.0–7.4 (`sh run-tests.sh`).
- No PrestaShop/WooCommerce references introduced in core or core tests.
