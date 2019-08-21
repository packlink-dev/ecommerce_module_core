# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [Unreleased](https://github.com/packlink-dev/ecommerce_module_core/compare/master...dev)
### Added
- Tests for `AbstracGenericStudentRepository` and `AbastractGenericQueueItemRepository` have been improved
to support multiple conditions in filters for select.

### Changed
**BREAKING CHANGES**
- `ShopShippingMethodService` has been extended with methods for adding and deleting backup carrier.
- `Proxy::sendDraft()` method will now throw an exception if the draft response does not contain the reference.

## [v1.3.1](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.3.1...1.3.0) - 2019-07-16
### Changed
  - Fixed sending draft full address.

## [v1.3.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.3.0...1.2.2) - 2019-07-10
### Changed
  - Updated task for updating shipment data to report the progress and handle base errors.

### Removed
**BREAKING CHANGES**
- Removed Zend framework PDF library since it creates a lot of dependencies and might conflict with 
other systems. Because of this, utility `PdfMerge` is removed!
To use PDF merging for shipping labels, do `composer require iio/libmergepdf:3.0.0`. 
Then, in your code add the following:
    ```
    $merger = new \iio\libmergepdf\Merger();
    $merger->addIterator($paths);
    $file = $merger->merge();
    ```
    where `$paths` is the array of local paths of the files. 
    `$file` will be a string representation of the resulting PDF file.
- Removed symfony php 5.4 and 5.5 utilities since they are not needed. Added only custom implementation
of `array_column` in class `Packlink\BusinessLogic\Utility\Php\Php55`.
- Removed `Proxy::callAsync` method as it is not used nor needed.

## [v1.2.2](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.2.2...v1.2.1) - 2019-06-20
### Added
- Added support for some PHP functions (e.g. `array_column`) that are not natively supported by
PHP versions prior to 5.5 by requiring Symfony packages that add this support.

## [v1.2.1](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.2.1...v1.2.0) - 2019-06-18
### Changed
- Analytics endpoint now adds module version as well
- JS `StateController` now supports additional configuration options in the `configuration` parameter:
  - `configuration.sidebarButtons`: can contain an array of keys for additional sidebar buttons
  - `configuration.submenuItems`: can contain specific settings submenu items (keys)
  - `configuration.pageConfiguration`: contains specific configuration for additional pages added 
in `configuration.sidebarButtons`. See `StateController.js` for the details of the implementation.

## [v1.2.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.2.0...v1.1.0) - 2019-05-31
**BREAKING CHANGES**:
### Added
- Business logic `ConfigurationService` has new abstract methods 
`getModuleVersion()`, `getECommerceName`, and `getECommerceVersion`.
- Custom HTTP headers are added to the `Proxy`. This required the above configuration service methods. (CR 14-01)
- Support for sending analytics based on certain events in the shop (CR Set 14-02).
Modules have to call methods from `AnalyticsController::sendOtherServicesDisabledEvent` if a user chooses to 
disable other shipping methods upon activating the first Packlink service.
- `OrderShipmentDetails` entity is added to the core. It holds the information about Packlink shipment for a 
specific shop order.

### Changed
- `Schedule` now had additional property for marking it recurrent (true by default). 
This enables creating one-time schedules.
- `Schedule` now has a context so that its tasks can hold the context data
- `Proxy::__contruct` has changed parameters since configuration service is now needed.
- Tests updated to follow new implementations.

## [v1.1.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.1.0...v1.0.0) - 2019-05-29
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

## [v1.0.0](https://github.com/packlink-dev/ecommerce_module_core/tree/v1.0.0) - 2019-05-24
- First stable release
