# Design: CR-SET-62b - Integration-Core Changes

> **Date:** 2026-04-20  
> **Spec:** [spec.md](spec.md)  
> **Tasks:** [tasks.md](tasks.md)  
> **Status:** Draft  
> **Layer:** integration-core

---

## How this fits the existing architecture

`ShipmentLabel` is already deeply embedded in the core — stored as arrays in
`OrderShipmentDetails`, batch-serialized, and consumed by `OrderService`, `Proxy`, and every
platform integration. Rather than replacing it (a breaking change), this CR adds a thin
`ShipmentDocument` DTO that wraps/adapts `ShipmentLabel` (and, for future document types such as
`ShipmentDocumentType::CUSTOMS_INVOICE`, other sources) into one presentation-layer shape that
platforms can render generically instead of branching on document-type-specific structures.

`ShipmentDocumentService::getDocumentsForOrder()` is the single aggregation point: it resolves
`OrderShipmentDetails` for the order, decides whether labels are fetchable yet
(`OrderService::isReadyToFetchShipmentLabels()`), and returns a flat `ShipmentDocument[]`
regardless of how many underlying document types exist behind it. This keeps the "what documents
can I show for this order" decision inside core rather than duplicated per platform.

`PrintService.js` is deliberately framework-free: an iframe-based print with a `window.open`
fallback, so every platform's frontend can load it as-is from the shared resources path with no
platform-specific wiring. The same-origin constraint means each platform must proxy external PDF
URLs through its own endpoint — that proxying is platform scope, not core scope.

See [tasks.md](tasks.md) for the ordered implementation tasks, dependency graph, and
files-changed-per-task breakdown.
