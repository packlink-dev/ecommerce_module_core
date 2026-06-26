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
| `CustomsMapping` (FrontDto) | `src/BusinessLogic/Customs/Models/CustomsMapping.php` | ✅ |
| `TaxIdOption` | `src/BusinessLogic/Customs/Models/TaxIdOption.php` | ✅ |
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

### 3.1 Common-UI design (customs settings page)

Today `Resources/templates/customs.html` renders a fixed **Data mapping** section with a single
`mapping_receiver_tax_id` select. The mockups require more mapping fields, and the set differs
per platform (WooCommerce shows **Company VAT**, PrestaShop does not). To stay platform-agnostic
the common UI renders the mapping section **generically** from platform-supplied option lists
rather than hardcoding fields:

- **`CustomsMapping` model** (`Customs/Models/CustomsMapping.php`) — add the new mapping fields
  to `$fields`, `fromArray()`, `toArray()` (e.g. `mapping_tariff_number` for the product HS code
  field; reuse `mapping_receiver_tax_id` for the customer tax id; add a field for company VAT
  where supplied). Keep them **optional** (not in `$requiredFields`).
- **`customs.html` + `CustomsController.js`** — render a mapping `<select>` per platform-supplied
  field definition (label + options), following the existing receiver-tax-id pattern
  (`configuration.getCustomData` populates options; `modelFields` is extended accordingly).
- **Core/platform boundary** — core defines the mapping fields it understands and renders the
  selects; the **options** (available product/customer attributes) and which optional fields are
  present come from the platform (e.g. an extended `getReceiverTaxIdOptions()`-style contract or
  the existing custom-data endpoint). Core must not reference PrestaShop/WooCommerce field names.
- **Consumption** — `Customs/CustomsService.php` already falls back to mapping/defaults when
  building the invoice; extend it to read any new mapping fields when assembling inventory
  (HS code) and receiver (tax id / company VAT) data.
- **Styles** — reuse existing `.pl-form-group` / `.pl-customs-label`; recompile SCSS only if new
  styles are added (`php cssCompile.php`). Add the new translation keys to `Resources/countries/*.json`.

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
