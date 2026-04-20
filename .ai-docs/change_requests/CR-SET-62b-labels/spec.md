# Spec: CR-SET-62b - Integration-Core Changes

> **Version:** 1.0  
> **Date:** 2026-04-20  
> **Status:** Draft  
> **Layer:** integration-core (shared across all Packlink modules)

---

## 1. Overview

The integration-core changes introduce a unified **document abstraction** for shipment-related documents (shipping labels, customs invoices) and a shared **PrintService** JavaScript module for browser-based PDF printing.

These changes are platform-agnostic. Each platform (WooCommerce, PrestaShop, Magento, Shopify) consumes the core services and JS module through their own HTTP layer and frontend.

---

## 2. ShipmentDocumentType

**File:** `src/BusinessLogic/ShipmentDocument/ShipmentDocumentType.php`  
**Namespace:** `Packlink\BusinessLogic\ShipmentDocument`

```php
class ShipmentDocumentType
{
    const SHIPPING_LABEL = 'shipping_label';
    const CUSTOMS_INVOICE = 'customs_invoice';

    /**
     * Returns all known document types.
     *
     * @return string[]
     */
    public static function getAll()
    {
        return array(
            self::SHIPPING_LABEL,
            self::CUSTOMS_INVOICE,
        );
    }

    /**
     * Returns a human-readable label for a document type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getLabel($type)
    {
        $labels = array(
            self::SHIPPING_LABEL  => 'Shipping label',
            self::CUSTOMS_INVOICE => 'Customs invoice',
        );

        return isset($labels[$type]) ? $labels[$type] : $type;
    }
}
```

> Note: `CUSTOMS_INVOICE` is included for future platform use. WooCommerce does not use it yet (no customs mapping implemented).

---

## 3. ShipmentDocument DTO

**File:** `src/BusinessLogic/ShipmentDocument/DTO/ShipmentDocument.php`  
**Namespace:** `Packlink\BusinessLogic\ShipmentDocument\DTO`  
**Extends:** `Logeecom\Infrastructure\Data\DataTransferObject`

Fields:
- `$type` (string) -- one of `ShipmentDocumentType` constants
- `$link` (string) -- URL to the PDF
- `$printed` (bool) -- whether the document has been printed/downloaded
- `$name` (string) -- human-readable name

Standard pattern: `fromArray()`, `toArray()`, getters, setters. Wraps existing `ShipmentLabel` data into a unified document representation without replacing `ShipmentLabel`.

---

## 4. ShipmentDocumentServiceInterface

**File:** `src/BusinessLogic/ShipmentDocument/Interfaces/ShipmentDocumentServiceInterface.php`  
**Namespace:** `Packlink\BusinessLogic\ShipmentDocument\Interfaces`

```php
interface ShipmentDocumentServiceInterface
{
    const CLASS_NAME = __CLASS__;

    /**
     * Returns all available documents for the given order.
     *
     * @param string $orderId
     *
     * @return ShipmentDocument[]
     */
    public function getDocumentsForOrder($orderId);

    /**
     * Marks a document as printed.
     *
     * @param string $shipmentReference
     * @param string $documentType One of ShipmentDocumentType constants.
     * @param string $link Document link (used to identify specific label).
     */
    public function markDocumentPrinted($shipmentReference, $documentType, $link);
}
```

---

## 5. ShipmentDocumentService

**File:** `src/BusinessLogic/ShipmentDocument/ShipmentDocumentService.php`  
**Namespace:** `Packlink\BusinessLogic\ShipmentDocument`

Dependencies (via `ServiceRegister`):
- `OrderShipmentDetailsService` -- to get `OrderShipmentDetails`
- `OrderService` -- to call `getShipmentLabels()` and `isReadyToFetchShipmentLabels()`

**`getDocumentsForOrder($orderId)` logic:**

1. Get `OrderShipmentDetails` by `$orderId`
2. If null, return empty array
3. Collect shipping labels:
   a. Check `OrderService::isReadyToFetchShipmentLabels()` on current status
   b. Get labels from `OrderShipmentDetails::getShipmentLabels()`
   c. If empty and status is ready, fetch from API via `OrderService::getShipmentLabels()`
   d. Convert each `ShipmentLabel` to `ShipmentDocument` with `type=SHIPPING_LABEL`
4. Return array

**`markDocumentPrinted()` logic:**

- If `$documentType === SHIPPING_LABEL`: delegate to `OrderShipmentDetailsService::markLabelPrinted($reference, $link)`

---

## 6. BootstrapComponent Registration

**File:** `src/BusinessLogic/BootstrapComponent.php`

Add service registration in `initServices()`:
```php
ServiceRegister::registerService(
    ShipmentDocumentServiceInterface::CLASS_NAME,
    function () {
        return new ShipmentDocumentService();
    }
);
```

---

## 7. PrintService JavaScript

**File:** `src/BusinessLogic/Resources/js/PrintService.js`

Pure browser JS with no platform dependencies. All platforms load it from their shared resources path (e.g., WooCommerce loads as `packlink/js/PrintService.js`).

IIFE module following existing `Packlink.*` namespace pattern:

```javascript
var Packlink = window.Packlink || {};

(function () {
    function PrintService() {
        /**
         * Prints a PDF by loading it in a hidden iframe and triggering
         * the browser's native print dialog.
         *
         * @param {string} url Same-origin URL to the PDF.
         * @param {function} [onComplete] Called after print dialog closes.
         */
        this.printPdf = function (url, onComplete) {
            var iframe = document.createElement('iframe');
            iframe.style.position = 'fixed';
            iframe.style.right = '0';
            iframe.style.bottom = '0';
            iframe.style.width = '0';
            iframe.style.height = '0';
            iframe.style.border = 'none';
            iframe.src = url;

            iframe.onload = function () {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    window.open(url, '_blank');
                }

                setTimeout(function () {
                    if (iframe.parentNode) {
                        iframe.parentNode.removeChild(iframe);
                    }
                    if (typeof onComplete === 'function') {
                        onComplete();
                    }
                }, 1000);
            };

            document.body.appendChild(iframe);
        };
    }

    Packlink.printService = new PrintService();
})();
```

Key design points:
- Hidden iframe avoids popup blockers (unlike `window.open()`)
- Same-origin requirement: platforms must provide a server-side proxy endpoint for external PDFs
- `try/catch` fallback handles cross-origin or browser restriction edge cases
- Cleanup delay of 1s allows print dialog to fully initialize
- `onComplete` callback enables page reload after bulk print

---

## 8. Files Summary

### New Files

| File | Description |
|------|-------------|
| `BusinessLogic/ShipmentDocument/ShipmentDocumentType.php` | Document type constants |
| `BusinessLogic/ShipmentDocument/DTO/ShipmentDocument.php` | Unified document DTO |
| `BusinessLogic/ShipmentDocument/Interfaces/ShipmentDocumentServiceInterface.php` | Service interface |
| `BusinessLogic/ShipmentDocument/ShipmentDocumentService.php` | Aggregation service |
| `BusinessLogic/Resources/js/PrintService.js` | Iframe-based browser print utility |

### Modified Files

| File | Change |
|------|--------|
| `BusinessLogic/BootstrapComponent.php` | Register `ShipmentDocumentService` |

---

## 9. Why Not Replace ShipmentLabel?

`ShipmentLabel` is deeply embedded in the core -- stored as arrays in `OrderShipmentDetails`, batch-serialized, and used in `OrderService`, `Proxy`, and all platform integrations. Replacing it would be a breaking change. Instead, `ShipmentDocument` wraps/adapts `ShipmentLabel` for the presentation layer.
