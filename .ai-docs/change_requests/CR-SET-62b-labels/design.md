# CR-SET-62b - Integration-Core Implementation Tasks

> **Date:** 2026-04-20  
> **Spec:** [spec-core.md](spec-core.md)  
> **Status:** Draft  
> **Layer:** integration-core

---

## Task 1: ShipmentDocumentType Constants

**Effort:** Small  
**Dependencies:** None

**Create** `src/BusinessLogic/ShipmentDocument/ShipmentDocumentType.php`

- Namespace: `Packlink\BusinessLogic\ShipmentDocument`
- Constants: `SHIPPING_LABEL = 'shipping_label'`, `CUSTOMS_INVOICE = 'customs_invoice'`
- Static methods: `getAll()`, `getLabel($type)`
- PHP 7.0 compatible (no enums)
- Note: `CUSTOMS_INVOICE` included for future platform use, not used by WooCommerce yet

---

## Task 2: ShipmentDocument DTO

**Effort:** Small  
**Dependencies:** Task 1

**Create** `src/BusinessLogic/ShipmentDocument/DTO/ShipmentDocument.php`

- Namespace: `Packlink\BusinessLogic\ShipmentDocument\DTO`
- Extends: `Logeecom\Infrastructure\Data\DataTransferObject`
- Fields: `$type` (string), `$link` (string), `$printed` (bool), `$name` (string)
- Methods: `fromArray()`, `toArray()`, getters, setters
- Wraps `ShipmentLabel` data into a unified document representation

---

## Task 3: ShipmentDocumentService

**Effort:** Medium  
**Dependencies:** Tasks 1, 2

**Create** `src/BusinessLogic/ShipmentDocument/Interfaces/ShipmentDocumentServiceInterface.php`

- `getDocumentsForOrder($orderId)` -- returns `ShipmentDocument[]`
- `markDocumentPrinted($shipmentReference, $documentType, $link)` -- marks document as printed

**Create** `src/BusinessLogic/ShipmentDocument/ShipmentDocumentService.php`

- Implements `ShipmentDocumentServiceInterface`
- Dependencies via `ServiceRegister`: `OrderShipmentDetailsService`, `OrderService`
- `getDocumentsForOrder()` logic:
  1. Get `OrderShipmentDetails` by orderId
  2. If null, return empty array
  3. Collect shipping labels:
     - Check `OrderService::isReadyToFetchShipmentLabels()` on status
     - Get from `getShipmentLabels()` or fetch from API if empty
     - Convert each `ShipmentLabel` to `ShipmentDocument` with `type=SHIPPING_LABEL`
  4. Return array
- `markDocumentPrinted()` logic:
  - `SHIPPING_LABEL` -> delegates to `OrderShipmentDetailsService::markLabelPrinted()`

**Modify** `src/BusinessLogic/BootstrapComponent.php`

- Register `ShipmentDocumentService` in `initServices()`

---

## Task 4: PrintService JavaScript

**Effort:** Small  
**Dependencies:** None

**Create** `src/BusinessLogic/Resources/js/PrintService.js`

- IIFE module: `Packlink.printService`
- Public method: `printPdf(url, onComplete)`
- Pure browser JS, no platform-specific dependencies -- reusable by all Packlink modules
- Implementation:
  1. Create hidden `<iframe>` (position: fixed, 0x0, no border)
  2. Set `iframe.src = url` (same-origin proxy URL)
  3. On `iframe.onload`: `iframe.contentWindow.focus()` then `iframe.contentWindow.print()`
  4. `try/catch` fallback to `window.open(url, '_blank')` if print() fails
  5. After 1s delay, remove iframe, call `onComplete` callback

---

## Task Dependencies

```
Task 1 (DocumentType)  ─┐
Task 2 (Document DTO)  ─┤──> Task 3 (DocumentService)
                         │
Task 4 (PrintService)  ──┘  (independent, can run in parallel)
```

**Parallelizable:** Tasks 1, 2, 4 can run in parallel. Task 3 depends on 1 and 2.

---

## Files Changed Per Task

| Task | New Files | Modified Files |
|------|-----------|----------------|
| 1 | `ShipmentDocument/ShipmentDocumentType.php` | -- |
| 2 | `ShipmentDocument/DTO/ShipmentDocument.php` | -- |
| 3 | `ShipmentDocument/ShipmentDocumentService.php`, `ShipmentDocument/Interfaces/ShipmentDocumentServiceInterface.php` | `BootstrapComponent.php` |
| 4 | `Resources/js/PrintService.js` | -- |
