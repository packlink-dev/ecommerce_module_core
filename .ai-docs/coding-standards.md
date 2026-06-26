# Coding Standards

> Authoritative style reference for `packlink/integration-core`. These standards are
> **derived from the existing code** in `src/` and `tests/` — follow them so new code is
> indistinguishable from what is already there. There is no automated linter (no
> phpcs/phpstan); these conventions are enforced by review.

---

## 1. PHP Language Target

- **Minimum PHP 7.0.** `composer.json` pins `config.platform.php = "7.0"`; code must run on **7.0 through 7.4**.
- **Do not use** any feature newer than 7.0:
  - No short array syntax `[]` — use `array()` everywhere.
  - No nullable type hints (`?Type`).
  - No typed properties.
  - No return type declarations (`: void`, `: array`, `: bool`, …).
  - No scalar parameter type hints where it would break 7.0 nuances — object/array/callable type hints are fine and widely used.
  - No `enum` (use classes with `const` + static helpers — see `ShipmentDocumentType`).
- All type information lives in **PHPDoc**, not in signatures.

## 2. Files & Classes

- One class/interface per file; filename matches the type name.
- File opens with `<?php`, then a blank line, then the `namespace` declaration. **No license/copyright header block.**
- PSR-4 roots (see `composer.json`):
  - `Logeecom\Infrastructure\` → `src/Infrastructure`
  - `Packlink\BusinessLogic\` → `src/BusinessLogic`
  - `Packlink\Brands\` → `src/Brands`
  - Tests: `Logeecom\Tests\Infrastructure\`, `Logeecom\Tests\BusinessLogic\`, `Logeecom\Tests\Brands\` → `tests/...`
- Every class and interface declares the service-locator key as the first member:

  ```php
  /**
   * Fully qualified name of this class.
   */
  const CLASS_NAME = __CLASS__;
  ```

## 3. Naming

| Element | Convention | Example |
|---|---|---|
| Class | PascalCase | `OrderService`, `ServiceRegister` |
| Interface | PascalCase + `Interface` suffix | `RepositoryInterface`, `OrderServiceInterface` |
| Abstract class | PascalCase, often `Abstract` prefix | `AbstractIntegrationDataProvider` |
| Method / property | camelCase | `prepareDraft()`, `$shopOrderService` |
| Constant | UPPER_SNAKE_CASE | `CLASS_NAME`, `MIN_LOG_LEVEL` |
| Test class | PascalCase + `Test` suffix | `ServiceRegisterTest` |
| Test method | `test` + PascalCase | `testGetInstance()` |

## 4. Formatting

- 4-space indentation, no tabs.
- Opening brace on the **same line** for classes, methods, and control structures.

  ```php
  class OrderService
  {
      public function prepareDraft(Order $order)
      {
          if ($order->isEmpty()) {
              // ...
          }
      }
  }
  ```

## 5. Docblocks

- **Class-level** docblock with a short description and a `@package` tag.
- **Every public method** documents `@param`, `@return`, and `@throws`.
- **Properties** document `@var`.

  ```php
  /**
   * Prepares shipment draft object for order with provided unique identifier.
   *
   * @param Order $order
   *
   * @return Draft Prepared shipment draft.
   *
   * @throws EmptyOrderException When order has no items.
   */
  public function prepareDraft(Order $order)
  ```

- IDE/inspection suppression comments (`@noinspection PhpDocMissingThrowsInspection`, etc.) are used sparingly where appropriate.

## 6. Services & Singletons

- Shared services extend `Logeecom\Infrastructure\Singleton`.
- Each subclass **must declare its own** `protected static $instance;` (the base class documents this requirement).
- Use the inherited `getInstance()` / `resetInstance()`; the constructor is `protected` and resolves dependencies via the service locator:

  ```php
  protected function __construct()
  {
      $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
  }
  ```

- Register services in a `BootstrapComponent::initServices()` with a lazy factory keyed on `CLASS_NAME`:

  ```php
  ServiceRegister::registerService(MyService::CLASS_NAME, function () {
      return MyService::getInstance();
  });
  ```

- **Platform-variable behavior goes behind an abstract class or interface** — the core must never reference a concrete e-commerce platform. Examples: `Configuration`, `HttpClient`, `RepositoryInterface`, `ShopOrderService`, `ShopShippingMethodService`.

## 7. DTOs

- DTOs extend `Logeecom\Infrastructure\Data\DataTransferObject`. API/front-facing DTOs extend `Packlink\BusinessLogic\DTO\FrontDto`.
- Declare `protected static $fields = array();` listing serialized properties; `FrontDto` adds `protected static $requiredFields = array();`.
- `fromArray(array $raw)` validates (for `FrontDto`) then hydrates via `property_exists(static::CLASS_NAME, $field)`.
- `toArray()` iterates `static::$fields`.
- `FrontDto` validation throws `FrontDtoValidationException` carrying `ValidationError[]`.
- Register front DTOs with string keys in `FrontDtoFactory` during bootstrap.

## 8. Tests

- Extend `BaseTestWithServices` (business logic), `BaseInfrastructureTestWithServices` (infrastructure), or `BaseSyncTest` (task/sync) — these wire up `TestServiceRegister`.
- Use the PHPUnit `@before` / `@after` annotations (not `setUp()` / `tearDown()`) and call the parent hook to chain initialization.
- **PHPUnit 4.8** — no `void` return types on test methods; use the older assertion style (`$this->assertEquals(...)`, `$this->assertInstanceOf(...)`).
- Use in-memory repositories (`MemoryRepository`, `MemoryQueueItemRepository`) for isolation.
- Capture/inspect HTTP with `TestHttpClient`; supply config with `TestShopConfiguration`.

## 9. Frontend Resources

- JavaScript lives in `src/BusinessLogic/Resources/js/`.
- Use the `window.Packlink` IIFE namespace pattern; controllers use the `*Controller` suffix:

  ```javascript
  if (!window.Packlink) {
      window.Packlink = {};
  }

  (function () {
      function MyController(configuration) {
          // ...
      }

      Packlink.myController = MyController;
  })();
  ```

- SCSS lives in `src/BusinessLogic/Resources/scss/` and compiles to `Resources/css/` via `php cssCompile.php` (`leafo/scssphp`). Compilation also runs automatically on `composer install` / `composer update`. **Edit SCSS, never the generated CSS.**
