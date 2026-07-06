# Design: CR-SET-66 — Customs Support (integration-core)

> **Version:** 1.0
> **Date:** 2026-06-25
> **Status:** Draft
> **Layer:** integration-core
> **Spec:** [spec.md](spec.md)

---

## 1. Architecture Recap (existing customs flow)

The customs feature is already wired end-to-end in core. CR-SET-66 reuses it; this section
documents the flow so the (mostly test-coverage) deltas in §3 are clear.

```
[Merchant] ── Customs settings page (customs.html / CustomsController.js)
                 │  GET getData / POST save
                 ▼
        CustomsController ──► CustomsMappingService ──► Configuration (customsMappings)
                                     │
                                     ▼  defaults + receiver-tax-id mapping (CustomsMapping)
        ┌──────────────────────────────────────────────────────────────┐
        │ Order placed → SendDraftBusinessTask.execute()                 │
        │   CustomsService.shouldCreateCustoms(country, zip)             │
        │     └─ isShipmentInternational → Proxy.getCustomsByPostalCode  │
        │   if true: CustomsService.sendCustomsInvoice(order)            │
        │     └─ build CustomsInvoice (Sender/Receiver/Inventory/        │
        │        ShipmentDetails/Signature) → Proxy.sendCustomsInvoice   │
        │   Draft.hasCustoms = true; Draft.customs.customs_invoice_id    │
        │   Proxy.sendDraft(draft) → shipment reference                  │
        └──────────────────────────────────────────────────────────────┘
                                     │
                                     ▼
        OrderService.updateShipmentData(shipment, customsId)
            └─ updateShipmentCustomsData → OrderShipmentDetails.customsInvoiceId (persisted)
                                     │
                                     ▼
        ShipmentDocumentService.getDocumentsForOrder(orderId)
            └─ collectCustomsInvoice → Proxy.getCustomsInvoiceDownloadUrl
               → ShipmentDocument(CUSTOMS_INVOICE, url, …)  ─► [platform order-details page]
                                                              uses Packlink.printService.printPdf
```

## 2. Components & Responsibilities

| Component | File | Status |
|-----------|------|--------|
| `CustomsController` (CRUD) | `src/BusinessLogic/Controllers/CustomsController.php` | ✅ |
| `CustomsMappingService` (+ interface) | `src/BusinessLogic/Customs/CustomsMappingService.php`, `Customs/Interfaces/CustomsMappingServiceInterface.php` | ✅ |
| `CustomsMapping` (FrontDto) | `src/BusinessLogic/Customs/Models/CustomsMapping.php` | ✅ (extended: `mapping_tariff_number`, `mapping_company_vat`) |
| `TaxIdOption` | `src/BusinessLogic/Customs/Models/TaxIdOption.php` | ✅ |
| `MappingFieldOptions` (new) | `src/BusinessLogic/Customs/Models/MappingFieldOptions.php` | ✅ new |
| `CustomsService` (+ interface) | `src/BusinessLogic/Customs/CustomsService.php`, `Customs/Interfaces/CustomsService.php` | ✅ |
| Customs invoice DTOs | `src/BusinessLogic/Http/DTO/Customs/*` (`CustomsInvoice`, `Sender`, `Receiver`, `InventoryContent`, `ShipmentDetails`, `Signature`, `Cost`, `Money`, `CustomsUnionsSearchRequest`) | ✅ |
| Draft customs | `src/BusinessLogic/Http/DTO/Draft.php` (`hasCustoms`), `Http/DTO/Draft/Customs.php` | ✅ |
| Proxy customs calls | `src/BusinessLogic/Http/Proxy.php` (`sendCustomsInvoice`, `getCustomsByPostalCode`, `getCustomsInvoiceDownloadUrl`) | ✅ |
| Draft creation hook | `src/BusinessLogic/Tasks/BusinessTasks/SendDraftBusinessTask.php` | ✅ |
| Shipment persistence | `src/BusinessLogic/Order/OrderService.php`, `OrderShipmentDetails/Models/OrderShipmentDetails.php`, `OrderShipmentDetails/OrderShipmentDetailsService.php` | ✅ |
| Document exposure | `src/BusinessLogic/ShipmentDocument/ShipmentDocumentService.php`, `ShipmentDocument/ShipmentDocumentType.php`, `ShipmentDocument/DTO/ShipmentDocument.php` | ✅ |
| Print helper | `src/BusinessLogic/Resources/js/PrintService.js` | ✅ |
| Settings UI | `src/BusinessLogic/Resources/templates/customs.html`, `Resources/js/CustomsController.js`, `Resources/templates/configuration.html` | ✅ |
| Bootstrap / DTO registry | `src/BusinessLogic/BootstrapComponent.php` (`CustomsService`, `ShipmentDocumentService`, `FrontDtoFactory` ← `CustomsMapping`) | ✅ |

## 3. Deltas & Rationale

The CR-SET-42 implementation already covers the customs **back-end** surface (spec §4 shows
full schema coverage). Two kinds of core delta remain for CR-SET-66:

1. **Common UI extension (§3.1)** — the customs settings page must render the additional
   mapping selects shown in the mockups (Product HS code field, Customer tax ID field, and the
   platform-variant Company VAT). This touches the common UI and the `CustomsMapping` model.
   Task T6.

2. **Automated test coverage** for the customs units that are untested or partially tested
   (`CustomsMapping`, `CustomsService`, parts of `CustomsController`, the `CUSTOMS_INVOICE`
   document path, draft→shipment propagation). Protects the feature as the two platforms begin
   consuming it. Tasks T1–T5.

The order-details print/download buttons (spec UC-7) need **no** core production change — the
building blocks (`ShipmentDocumentService`, `Proxy::getCustomsInvoiceDownloadUrl`,
`PrintService.js`, `ShipmentDocument(CUSTOMS_INVOICE)`) already exist; each platform renders the
buttons on its own order-details page.

### 3.1 Common-UI design (customs settings page) — as implemented

`Resources/templates/customs.html` previously rendered a fixed **Data mapping** section with a
single `mapping_receiver_tax_id` select. The mockups require more mapping fields, and the set
differs per platform (WooCommerce shows **Company VAT**, PrestaShop does not). To stay
platform-agnostic the common UI now renders the mapping section **generically** from a
platform-supplied field list, fully replacing the old single-field wiring:

- **`CustomsMapping` model** (`Customs/Models/CustomsMapping.php`) — added `mapping_tariff_number`
  (product HS code field) and `mapping_company_vat` (platform-variant company VAT field) to
  `$fields`, `fromArray()`, `toArray()`. Both are **optional** (not in `$requiredFields`), same as
  the pre-existing `mapping_receiver_tax_id`.
- **New DTO `Customs/Models/MappingFieldOptions.php`** — `{field, label, options: TaxIdOption[]}`.
  One instance describes one renderable mapping select: which `CustomsMapping` field it targets,
  its display label, and its selectable options. `TaxIdOption` (pre-existing `{value, name}`) is
  reused as the option shape.
- **Contract change** — `CustomsMappingServiceInterface`/`CustomsMappingService` renamed the old
  single-purpose `getReceiverTaxIdOptions(): TaxIdOption[]` to
  `getMappingFieldsOptions(): MappingFieldOptions[]`. `CustomsController` exposes the renamed
  method; `DemoUI` (reference implementation) and `MockCustomsMappingService` (test double) were
  updated accordingly. **This is a breaking change** to the abstract contract platform modules
  implement — PrestaShop/WooCommerce wiring (tracked in their own CRs) must implement the new
  method instead of the old one.
- **`customs.html`** — the static `mapping_receiver_tax_id` `<select>` was replaced with an empty
  `<div id="pl-mapping-fields">` container.
- **`CustomsController.js`** — `constructMappingFields(response)` iterates the
  `MappingFieldOptions[]` returned by `configuration.getCustomData`, and for each entry builds a
  `.pl-form-group` with a `<select name="{field}">` (options + previously-saved value), a label
  using the platform-supplied `label` text, and pushes `field` onto `this.modelFields` so the
  existing generic pre-fill/submit logic (`getFormFields()`) picks it up automatically. No field
  names or labels are hardcoded in core JS.
- **Core/platform boundary** — both the **label** and the **options** for each mapping field come
  from the platform via `getMappingFieldsOptions()`; core only defines the `CustomsMapping` field
  keys it understands (`mapping_receiver_tax_id`, `mapping_tariff_number`, `mapping_company_vat`)
  and renders whatever subset of them the platform's response includes. Core does not reference
  PrestaShop/WooCommerce field names.
- **Consumption in `CustomsService.php` — deliberately NOT wired.** The `mapping_*` fields are
  attribute-*selectors* (which system/custom field a value comes from), consumed exclusively by
  the platform when it builds the `Order`/`Item`/`Address` objects it hands to core (the same as
  the pre-existing `mapping_receiver_tax_id`, which `CustomsService` has never read). `CustomsService`
  already falls back to the `default_*` **value** fields (`defaultTariffNumber`, `defaultCountry`,
  `defaultReceiverTaxId`) when the shop object doesn't carry a value — that fallback path is
  unchanged and requires no new wiring for the two new mapping-selector fields.
- **Styles** — reused existing `.pl-form-group` / `.pl-customs-label`; no new SCSS, no
  `cssCompile.php` run needed.
- **Translations** — no new core translation keys were added. Since both the label and options of
  every mapping field are platform-supplied (see above), the mapping section no longer depends on
  any core-owned translation string; `customs.receiverTaxId` remains in use elsewhere (the
  `default_receiver_tax_id` input's label) and was left untouched.

**Observation (no task, out of scope for this CR):** `CustomsService::getReceiver()` compares
`$mapping->defaultReceiverUserType` (schema-cased, e.g. `"PRIVATE_PERSON"`, as stored by
`CustomsControllerTest`/`SendDraftTaskTest` fixtures and as required by Packlink's
`receiver.user_type` enum) against the lowercase `self::PRIVATE_PERSON`/`self::COMPANY`
constants. The comparison never matches for schema-cased mapping values, so `receiver.tax_id` /
`receiver.company_name` / `receiver.vat_number` end up empty regardless of the merchant's actual
selection. `CustomsServiceTest::testSendCustomsInvoiceBuildsPayloadAndReturnsId` documents this as
current behavior rather than silently "fixing" logic outside this CR's stated gaps. Worth a
follow-up ticket if confirmed unintended.

**Observation (no task):** `CustomsMapping` declares return types (`: array`, `: CustomsMapping`)
which are valid on PHP 7.0 but stylistically inconsistent with the rest of core
(`.ai-docs/coding-standards.md` discourages return-type declarations). Left as-is to avoid
churn; flag if the team wants a style sweep.

## 4. Test Design

Tests follow the house pattern (see `CustomsControllerTest`):

- Extend `BaseTestWithServices`; use `@before` / `@after` annotations (not `setUp`/`tearDown`),
  chaining `parent::before()` / `parent::after()` where the base provides it.
- Register services through `TestServiceRegister` and repositories through
  `RepositoryRegistry` / `TestRepositoryRegistry` with `MemoryRepository`.
- Register front DTOs via `TestFrontDtoFactory` (`CustomsMapping::CLASS_KEY`,
  `ValidationError::CLASS_KEY`).
- Drive HTTP with `TestHttpClient`, queuing the existing fixtures under
  `tests/BusinessLogic/Common/ApiResponses/Customs/` (`searchResult.json`,
  `emptySearchResult.json`, `createCustomsResult.json`, `downloadUrl.json`).
- Use `MockCustomsMappingService` (`tests/BusinessLogic/Common/TestComponents/Customs/`) where
  the abstract `getReceiverTaxIdOptions()` must return fixed data.
- PHPUnit 4.8 assertion style; no `void` return types on test methods.

Representative shape:

```php
public function testTariffNumberMustBeSixToEightDigits()
{
    $payload = $this->validMappingPayload();
    $payload['default_tariff_number'] = '123'; // too short

    $this->expectException(FrontDtoValidationException::class);
    CustomsMapping::fromArray($payload);
}
```

## 5. Coding Standards & Best Practices

All code added by this CR follows `.ai-docs/coding-standards.md`:
- PHP 7.0 — `array()` syntax, no nullable types/typed properties, PHPDoc for types.
- `@before`/`@after` test setup chaining; in-memory repositories for isolation.
- Reuse existing fixtures and test doubles rather than inventing new HTTP mocks.
- Keep the core platform-agnostic — no PrestaShop/WooCommerce references in core or its tests.

See [tasks.md](tasks.md) for the ordered work items.
