# Spec: CR-SET-66 — Customs Support (integration-core)

> **Version:** 1.0
> **Date:** 2026-06-25
> **Status:** Draft
> **Layer:** integration-core (shared across all Packlink modules)

---

## 1. Overview & Scope

CR-SET-66 adds international **customs support** to the PrestaShop and WooCommerce modules.
The requirement was "previously specified and implemented in Shopify as part of CR-SET-42",
and that work landed the **platform-agnostic core** in this library. This spec captures the
**core** requirements as use cases with acceptance criteria, and gap-checks each one against
the code that already exists.

**In scope (this document):** the platform-agnostic core — customs settings/mapping, the
customs invoice DTO tree and proxy calls, internationality detection, invoice creation during
draft, shipment-sync persistence (`has_customs` / `customs_invoice_id`), and the building
blocks that let a platform render print/download of the customs invoice.

**Out of scope (platform modules — PrestaShop / WooCommerce):**
- Adding HS code / country-of-origin fields to products and tax-ID/VAT to customers, including
  install/migration prepopulation of plugin defaults.
- The platform order-details page and its print/download buttons (each module wires these to
  the core building blocks in §UC-7).
- Custom-attribute mapping UIs specific to a platform's entity model.

## 2. Status Legend & Baseline

| Tag | Meaning |
|-----|---------|
| ✅ | Implemented in core today (CR-SET-42) — CR-SET-66 reuses as-is. |
| ⚠️ | Gap — core change or added test coverage required (see `tasks.md`). |

> Because the core was built for Shopify, the bulk of CR-SET-66's core surface is already
> present. The remaining core work is predominantly **test coverage** plus one recorded
> data-mapping decision (UC-2).

---

## 3. Use Cases & Acceptance Criteria

### UC-1 — Configure customs default values
**Actor:** Merchant (via the platform's Packlink configuration UI).
**Description:** The merchant opens a dedicated **Customs** settings page and sets the default
values used to build customs invoices.

**Acceptance Criteria**
- ✅ A "Customs" item appears in the configuration menu and routes to the customs page —
  `Resources/templates/configuration.html` (`#pl-navigate-customs`) + `Resources/js/ConfigurationController.js` → `state.goToState('customs')`.
- ✅ The page renders default-value inputs: reason for export, sender tax id, receiver user
  type, receiver tax id, tariff number, country of origin — `Resources/templates/customs.html`, `Resources/js/CustomsController.js`.
- ✅ Country-of-origin options are populated from `CustomsController::getAllCountries()` (`CountryCodes::$countryCodes`).
- ✅ Current values are loaded via `CustomsController::getData()` and persisted via `CustomsController::save($data)` → `CustomsMappingService::updateCustomsMapping()`.
- ✅ Mapping persists per system/tenant through `Configuration` (`customsMappings` key).

### UC-2 — Map customs fields to system attributes
**Actor:** Merchant.
**Description:** On the Customs settings page, beyond the default values, the merchant maps
which system field / custom attribute holds each customs value the platform does not natively
store. The mockups (`requirements/images/packlink_export_img005.png` PrestaShop,
`…_img008.png` WooCommerce) show, in the **Default values** section, mapping dropdowns for
**Product HS code field** and **Customer tax ID field**, plus a platform-specific
**Company VAT** field on WooCommerce.

**Acceptance Criteria**
- ✅ Default values cover the no-mapping path — `default_tariff_number`, `default_country`,
  `default_sender_tax_id` on `CustomsMapping`; per-item values override when present
  (`Order\Objects\Item::getTariffNumber()` / `getCountryOfOrigin()`).
- ✅ A data-mapping select for the customer/receiver tax id exists today
  (`mapping_receiver_tax_id`; options via `CustomsController::getReceiverTaxIdOptions()` →
  `TaxIdOption[]`).
- ⚠️ The customs page must additionally render a **Product HS code field** mapping select and,
  where the platform supplies it, a **Company VAT** mapping select — matching the mockups. This
  requires extending the common UI (`Resources/templates/customs.html`,
  `Resources/js/CustomsController.js`) and the `CustomsMapping` model (new mapping fields). See
  `design.md` §3.1 and `tasks.md` T6.
- ⚠️ Mapping-select **options** are platform-specific (available product/customer attributes
  differ between PrestaShop and WooCommerce). Core must render the mapping section generically
  from platform-supplied option lists and must **not** hardcode platform fields. The set of
  mapping fields shown is therefore driven by the data the platform returns.

### UC-3 — Validate customs mapping input
**Actor:** System (on save).
**Description:** Invalid customs configuration is rejected with field-level errors before
persistence.

**Acceptance Criteria**
- ✅ `default_reason`, `default_sender_tax_id`, `default_receiver_user_type` are required —
  `CustomsMapping::$requiredFields` (enforced by `FrontDto::validate()`).
- ✅ `default_tariff_number` must match `^[0-9]{6,8}$` (6–8 digit HS code) — `CustomsMapping::doValidate()`.
- ✅ Validation failures raise `FrontDtoValidationException` carrying `ValidationError[]`.
- ⚠️ No automated test currently asserts the validation rules (see `tasks.md` T1, T3).

### UC-4 — Determine whether a shipment needs customs
**Actor:** System (during draft creation).
**Description:** The system decides if a shipment crosses a customs border and whether an
invoice should be created.

**Acceptance Criteria**
- ✅ `CustomsService::isShipmentInternational($countryCode, $postalCode)` queries
  `Proxy::getCustomsByPostalCode()` (`POST customs-unions/search-by-postal-code`).
- ✅ `CustomsService::shouldCreateCustoms($countryCode, $postalCode)` additionally requires a
  complete warehouse (name, address, city, country, postal code, phone) before creating customs.
- ⚠️ No automated test covers internationality / should-create logic (see `tasks.md` T2).

### UC-5 — Create and send the customs invoice on draft
**Actor:** System (`SendDraftBusinessTask`).
**Description:** For international shipments, a customs invoice is assembled from order +
warehouse + mapping data and sent to Packlink, returning a customs invoice id.

**Acceptance Criteria**
- ✅ `SendDraftBusinessTask` calls `CustomsService::sendCustomsInvoice($order)` when
  `shouldCreateCustoms()` is true, sets `Draft::$hasCustoms = true`, and attaches
  `Draft\Customs` with the returned id.
- ✅ The assembled `CustomsInvoice` covers the full requirement schema (see §4) and is sent via
  `Proxy::sendCustomsInvoice()` (`POST customs-invoices`).
- ✅ Sender/receiver user type resolves to `PRIVATE_PERSON` / `COMPANY` with the conditional
  tax-id / company-name / VAT rules from the schema.
- ⚠️ No focused test asserts the assembled payload / id propagation (see `tasks.md` T2, T5).

### UC-6 — Persist customs id and extend shipment synchronization
**Actor:** System (draft response + webhook update).
**Description:** The customs invoice id is stored against the order and the shipment payload
carries the customs flags.

**Acceptance Criteria**
- ✅ `Draft` serializes `has_customs` and `customs.customs_invoice_id` (`Http/DTO/Draft.php`, `Http/DTO/Draft/Customs.php`).
- ✅ `OrderService::updateShipmentData($shipment, $customsId)` delegates to
  `updateShipmentCustomsData()` → `OrderShipmentDetailsService`.
- ✅ `OrderShipmentDetails::$customsInvoiceId` is persisted (getter/setter on the entity).
- ⚠️ No focused test asserts draft→shipment customs propagation (see `tasks.md` T5).

### UC-7 — Expose the customs invoice for download / print (core building blocks)
**Actor:** Merchant (on a platform order-details page).
**Description:** Once a customs invoice exists, the core exposes it as a printable/downloadable
document; the platform order-details page renders the buttons (mockups:
`requirements/images/packlink_export_img004.png` PrestaShop "Print/Download customs invoice",
`…_img007.png` WooCommerce "Customs label" Download/Print).

**Acceptance Criteria**
- ✅ `ShipmentDocumentType::CUSTOMS_INVOICE` exists with a human-readable label.
- ✅ `ShipmentDocumentService::getDocumentsForOrder($orderId)` returns a `ShipmentDocument` of
  type `CUSTOMS_INVOICE` when `customsInvoiceId` is set, resolving the URL via
  `Proxy::getCustomsInvoiceDownloadUrl()`; API failure is logged and yields no document
  (`collectCustomsInvoice()`).
- ✅ `Resources/js/PrintService.js` (`Packlink.printService.printPdf(url, onComplete)`) provides
  same-origin iframe printing reusable by every platform.
- ➖ The order-details page and its buttons are **platform scope** (out of this spec).
- ⚠️ Confirm `ShipmentDocumentServiceTest` exercises the `CUSTOMS_INVOICE` path (see `tasks.md` T4).

---

## 4. Requirement Schema → Core DTO Traceability

| Requirement attribute | Core DTO field | Location |
|---|---|---|
| `invoice_number` | `CustomsInvoice::$invoiceNumber` | `Http/DTO/Customs/CustomsInvoice.php` |
| `reason_for_export` | `CustomsInvoice::$reasonForExport` | same |
| `sender.*` (user_type, full_name, tax_id, company_name, vat_number, eori_number, address, postal_code, city, country, phone_number) | `Sender` | `Http/DTO/Customs/Sender.php` |
| `receiver.*` (same shape as sender) | `Receiver` | `Http/DTO/Customs/Receiver.php` |
| `inventory_of_contents[]` (tariff_number, description, country_of_origin, item_value{currency,value}, item_weight, quantity) | `InventoryContent` (+ `Money`) | `Http/DTO/Customs/InventoryContent.php` |
| `shipment_details` (parcels_size, parcels_weight, cost{currency,value}) | `ShipmentDetails` (+ `Cost`) | `Http/DTO/Customs/ShipmentDetails.php` |
| `signature` (full_name, city) | `Signature` | `Http/DTO/Customs/Signature.php` |
| shipment `has_customs` | `Draft::$hasCustoms` | `Http/DTO/Draft.php` |
| shipment `customs.customs_invoice_id` | `Draft\Customs::$customsInvoiceId`; `OrderShipmentDetails::$customsInvoiceId` | `Http/DTO/Draft/Customs.php`; `OrderShipmentDetails/Models/OrderShipmentDetails.php` |

No requirement field is unmapped in core.

## 5. Gap Summary

| # | Gap | Type | Tasks |
|---|-----|------|-------|
| G1 | `CustomsMapping` validation untested (required fields, tariff regex, round-trip) | Test | T1 |
| G2 | `CustomsService` untested (internationality, should-create, invoice assembly + send) | Test | T2 |
| G3 | `CustomsController` partially tested (`getAllCountries`, `getReceiverTaxIdOptions`, save-error path missing) | Test | T3 |
| G4 | `CUSTOMS_INVOICE` document path coverage to confirm/extend | Test | T4 |
| G5 | Draft→shipment customs propagation (`has_customs`, `customs_invoice_id`) untested | Test | T5 |
| G6 | Customs page mapping selects (Product HS code field, Company VAT) + `CustomsMapping` model + generic platform-driven option rendering | UI + code | T6 |

## 6. Out of Scope / Platform Responsibilities

- PrestaShop & WooCommerce: add product HS code + country of origin, customer tax ID/VAT; on
  install/migration prepopulate the core customs defaults.
- Render the order-details print/download buttons against `ShipmentDocumentService` +
  `Packlink.printService`.
- These are tracked in the respective platform CR documents, not here.
