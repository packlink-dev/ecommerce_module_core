# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [Unreleased](https://github.com/packlink-dev/ecommerce_module_core/compare/master...dev)

## [2.0.3](https://github.com/packlink-dev/ecommerce_module_core/compare/v2.0.2...v2.0.3) - 2019-04-07
### Added
- `CountryService` now has a method for detecting if a given county is one of the four base countries.
- `OrderShipmentDetails` model is extended to have the shipment URL. It is set automatically when reference is set.

## [2.0.2](https://github.com/packlink-dev/ecommerce_module_core/compare/v2.0.1...v2.0.2) - 2019-04-03
### Changed
- Fix registration link for Austria.

## [2.0.1](https://github.com/packlink-dev/ecommerce_module_core/compare/v2.0.0...v2.0.1) - 2019-03-26
### Changed
- Fixed location picker input label
- Order shipment data will now be immediately updated after the draft has been created. 
This is added to immediately set the correct status and Packlink price.
- Changed registration links for all countries.

## [2.0.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.5.2...v2.0.0) - 2019-03-05

**BREAKING CHANGES**
This release contains several breaking changes. Check in detail when updating to this version.
Contains implementation of the "*Core Enhancements 1*" set of features. 
Check the documentation for more info.

### Added
- Added a `TaskClenupTask` for removing unnecessary queue items (tasks) from the database.
- `OrderShipmentDetailsService` is added. This service is in charge of working with the `OrderShipmentDetails` entity.
- `OrderShipmentDetailsRepository` is added for getting and storing the `OrderShipmentDetails` entity.
- `SendDraftTask` and `UpdateShipmentDataTask` tasks were updated to reflect the above changes.
Most notably, they now call either `OrderShipmentDetailsService` or `ShopOrderService` separately where needed.
- Added task abortion functionality. If needed, a task should throw `AbortTaskExecutionException` to abort it.
Aborted tasks will not be restarted by the queue service.
- `FrontDTO` and associated factory `FrontDtoFactory` is added. Now, any frontend DTO should be instantiated 
only through the factory method. If input data is not correct, a `FrontDtoValidationException` will be thrown
containing `ValidationError` array.
- `ShopShippingMethodService` interface has a new method `getCarrierLogoFilePath`.
- `WarehouseService` is introduced to manipulate the data for default warehouse. 
This service should be used to fetch and save warehouse data instead of direct call to 
the `ConfigurationService`.

### Changed
- `OrderRepository` interface is changed. It is renamed to `ShopOrderService` and now the 
only responsibility of this service is to work with an order in the shop/integration.
Most of the methods are removed.
- `OrderShipmentDetails` entity does not contain a reference to a task anymore.
- `DraftController` is renamed to `ShipmentDraftService` and handles both immediate and delayed 
`SendDraftTask` enqueueing. It provides a method for getting current status of the SendDraftTask for specific order.
- `DashboardStatus` is now a frontend DTO and a signature for the generated array is changed.
- `Warehouse` DTO changed namespace.
- `ShippingMethodsController` now adds logo URL to the ShippingMethod, so integrations do not need to set it.

## [v1.5.2](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.5.1...v1.5.2) - 2019-12-04
### Changed
- Replaced `substr` with `mb_substring` to prevent cutting the string in the middle of the special unicode character.

## [v1.5.1](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.5.0...v1.5.1) - 2019-11-28
### Changed
- `AjaxService.js:call` method now removes the protocol from the URL in order to use the current page's protocol.

## [v1.5.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.4.1...v1.5.0) - 2019-11-18
### Added
- `isRegistered` method in `RepositoryRegistry`.
- `isShipmentLabelsAvailable` method in `OrderService`

### Removed
- `OrderService::updateShipmentLabel` method.
- `OrderRepository::isLabelSet` method.
- `OrderRepository::setLabelsByReference` method.

### Changed
**NON-BREAKING CHANGES**
- `SendDraftTask` is now idempotent.
- `ShippingMethodConfiguration` DTO and `ShippingMethod` model are modified to enable setting list of allowed
destination countries for a shipping method.
- `UpdateShipmentDataTask` checks for update with different frequencies for orders with different statuses.
*NOTICE* For existing users delete old scheduled `UpdateShipmentDataTasks` and schedule new ones in the same manner as 
they are scheduled in core.
- `AbstractGenericStudentRepositoryTest` extended to cover every ORM operator.

**BREAKING CHANGES**

- The lowest boundary in fixed price can be higher than zero. *NOTICE* Each integration for from input field must
remove "disabled" directive in template file as disabling from input field is now handled by js library.
- Advanced serialization mechanism has been implemented.`NativeSerializer` and `JsonSerializer` have been introduced.
This is a *breaking* change and each integration should register preferred serializer in bootstrap.
- `OrderRepository` introduced new method `getOrderReferencesWithStatus`. This method must be implemented in each
integration.
- Removed `OrderRepository::setLabelsByReference` method.

## [v1.4.1](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.4.0...v1.4.1) - 2019-10-14
### Added
- `DraftController` is added to decrease the code in integrations related to creation of the draft
- `ConfigurationService` is extended with a flag whether to run async process with POST or GET call, defaults to POST.
Integration can just override this constant and the task runner service will use that. Also, if integration has a 
validation in async process controller whether the call is a POST call, it should be removed if this flag is changed.

### Changed
- Fixed double spinner problem in shipping services page.
- Fixed tax selector bug - using the already selected value.
- Fixed some tests.

## [v1.4.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.3.1...v1.4.0) - 2019-08-28
### Added
- Tests for `AbstracGenericStudentRepository` and `AbastractGenericQueueItemRepository` have been improved
to support multiple conditions in filters for select.
- Auto-Test and Auto-Configuration features (CR-12)
- `UpdateShippingServicesTaskStatusController` for checking the current status of the `UpdateShippingServicesTask`.
- `TranslationMessages.js` is added to give an example of what should be provided by integrations. This does not need 
to be included on any page.

### Changed
This version contains several **BREAKING CHANGES**, so please review the log.

JS changes:
- `AjaxService.js` is now easily extendable so integrations can adjust the calls.
- `FooterController.js` now listens to the Escape key to close the debug popup.
- `ShippingMethodsController.js` is adjusted to support auto-configuration feature if the integration supports it.
Also, behavior for getting shipping services is changed. Now, controller expects a status check URL (given as the
`shippingMethodsGetStatusUrl` parameter of the State controller constructor config object) and it first
checks the status of the task for getting shipping services task. For this, new 
`UpdateShippingServicesTaskStatusController` can be used in integrations. If task completed, shipping services will be 
retrieved.
- `UtilityService.js` is extended with new method `pad`.

Other changes:
- `ShopShippingMethodService` has been extended with methods for adding and deleting backup carrier. They have to be
implemented in the integration.
- `Proxy::sendDraft()` method will now throw an exception if the draft response does not contain the reference.
- `Infrastructure/Configuration/Configuration` class is extended to support the auto-configuration feature.
- `HttpClient` is adjusted for the auto-configuration. Check the diff to see the method signature changes.
- `CurlHttpClient` is changed to support easier extendability. It is adjusted to the changes in `HttpClient`. It now
supports: 
  - a timeout on synchronous requests (60 seconds by default)
  - auto-redirect feature if the FOLLOW_LOCATION cannot be set
  - adjustments of the cURL parameters based on the custom HTTP options set in the configuration
  - some functions are renamed and signature changed - review the diff
  - it supports a single extension point in the method `executeCurlRequest`. This is called for
  both sync and async calls.
- `Logger\LogData` is converted to entity. This does not break current integrations.

## [v1.3.1](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.3.0...v1.3.1) - 2019-07-16
### Changed
  - Fixed sending draft full address.

## [v1.3.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.2.2...v1.3.0) - 2019-07-10
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

## [v1.2.2](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.2.1...v1.2.2) - 2019-06-20
### Added
- Added support for some PHP functions (e.g. `array_column`) that are not natively supported by
PHP versions prior to 5.5 by requiring Symfony packages that add this support.

## [v1.2.1](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.2.0...v1.2.1) - 2019-06-18
### Changed
- Analytics endpoint now adds module version as well
- JS `StateController` now supports additional configuration options in the `configuration` parameter:
  - `configuration.sidebarButtons`: can contain an array of keys for additional sidebar buttons
  - `configuration.submenuItems`: can contain specific settings submenu items (keys)
  - `configuration.pageConfiguration`: contains specific configuration for additional pages added 
in `configuration.sidebarButtons`. See `StateController.js` for the details of the implementation.

## [v1.2.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.1.0...v1.2.0) - 2019-05-31
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

## [v1.1.0](https://github.com/packlink-dev/ecommerce_module_core/compare/v1.0.0...v1.1.0) - 2019-05-29
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
