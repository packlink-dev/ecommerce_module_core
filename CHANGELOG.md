# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [Unreleased](https://github.com/logeecom/pl_module_core/compare/master...dev)

## [v1.2.0](https://github.com/logeecom/pl_module_core/compare/v1.2.0...v1.1.0) - 2019-05-31
**BREAKING CHANGES**:
### Added
- Business logic `ConfigurationService` has new abstract methods 
`getModuleVersion()`, `getECommerceName`, and `getECommerceVersion`.
- Custom HTTP headers are added to the `Proxy`. This required the above configuration service methods. (CR 14-01)
- Support for sending analytics based on certain events in the shop (CR Set 14-02).
Modules have to call methods from `AnalyticsController::sendOtherServicesDisabledEvent` if a user chooses to 
disable other shipping methods upon activating the first Packlink service.

### Changed
- `Schedule` now had additional property for marking it recurrent (true by default). 
This enables creating one-time schedules.
- `Proxy::__contruct` has changed parameters since configuration service is now needed.
- Tests updated to follow new implementations.

## [v1.1.0](https://github.com/logeecom/pl_module_core/compare/v1.1.0...v1.0.0) - 2019-05-29
**BREAKING CHANGES**:
### Added
- `OrderRepository` interface has a new method `isLabelSet($shipmentReference)`.

### Changed
- Method signature changed for `OrderRepository::updateTrackingInfo`.
- Method signature changed for `OrderService::updateShipmentLabel`.
- Method signature changed for `OrderService::updateShippingStatus`.
- Method signature changed for `OrderService::updateTrackingInfo`.
- Shipment labels are now fetched from Packlink only when order does not have labels set 
and shipment status is in one of:
    * READY_TO_PRINT
    * READY_FOR_COLLECTION
    * IN_TRANSIT
    * DELIVERED

## [v1.0.0](https://github.com/logeecom/pl_module_core/tree/v1.0.0) - 2019-05-24
- First stable release
