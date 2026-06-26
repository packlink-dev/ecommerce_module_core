# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is `packlink/integration-core`, a PHP library that provides the shared core for Packlink shipping integrations across e-commerce platforms (PrestaShop, WooCommerce, Magento, Shopify, etc.). Platform-specific modules consume this library and implement its abstract classes/interfaces. It is **not** a standalone application.

## AI Docs & Change Requests

All change requests, contribution guidelines, and coding standards live under `.ai-docs/`. Read the relevant docs before starting work:

- **`.ai-docs/coding-standards.md`** — authoritative coding conventions (PHP 7.0 idioms, naming, docblocks, DTO/Singleton/test patterns, frontend resources). Read this before writing code.
- **`.ai-docs/guidelines.md`** — the change-request / contribution workflow.
- **`.ai-docs/design.md`** — system-wide architecture reference (layers, ORM, task queue, Packlink API proxy, services, key flows, entity catalog, platform integration contract).

**Change-request pattern.** Each change request gets its own folder, `.ai-docs/change_requests/<CR-ID>/`, named with the ticket ID plus a short slug (e.g. `CR-SET-62b-labels`). It contains two documents:

- `spec.md` — *what & why*: overview, the concrete classes/DTOs/interfaces to add or change (with namespaces, fields, method signatures), New/Modified file tables, and rationale notes.
- `design.md` — *how*: ordered implementation tasks with effort and dependencies, a task-dependency graph, and a files-changed-per-task table.

See `.ai-docs/change_requests/CR-SET-62b-labels/` as the reference example.

## Build & Test Commands

```bash
# Install dependencies (also compiles SCSS)
composer install

# Run all tests
php vendor/bin/phpunit --configuration phpunit.xml

# Run a single test file
php vendor/bin/phpunit --configuration phpunit.xml tests/BusinessLogic/Location/LocationServiceTest.php

# Run a single test method
php vendor/bin/phpunit --configuration phpunit.xml --filter testMethodName

# Run tests across multiple PHP versions (requires 7.0-7.4 installed)
sh run-tests.sh

# Compile SCSS resources
php cssCompile.php
```

## Architecture

### Three-Layer Design

The codebase has three PSR-4 namespaces:

- **`Logeecom\Infrastructure\`** (`src/Infrastructure/`) — Platform-agnostic framework: ORM, HTTP clients, task execution queue, configuration, logging, serialization, event bus. No Packlink business concepts here.
- **`Packlink\BusinessLogic\`** (`src/BusinessLogic/`) — Packlink domain: shipping methods, warehouses, orders, shipment drafts, registration, country data, customs. Contains controllers that platform modules call from their HTTP endpoints.
- **`Packlink\Brands\`** (`src/Brands/`) — Brand-specific configuration and resources (currently only the Packlink brand with per-country JSON resource files).

`DemoUI` (`src/DemoUI/`) is a reference implementation showing how a platform integration wires everything together. It is excluded from test coverage.

### Dependency Injection via Service Locator

There is no DI container. All dependencies are resolved through `ServiceRegister`:

```php
// Registration (typically in BootstrapComponent subclasses)
ServiceRegister::registerService(MyService::CLASS_NAME, function () {
    return new MyService();
});

// Resolution
$service = ServiceRegister::getService(MyService::CLASS_NAME);
```

Services that extend `Singleton` must declare their own `protected static $instance` field. The `CLASS_NAME` constant (set to `__CLASS__`) is used as the service key throughout.

### Entity / Repository System

Entities extend `Logeecom\Infrastructure\ORM\Entity`. Each entity must implement `getConfig()` returning an `EntityConfiguration` with an `IndexMap`. Repositories are registered per-entity class via `RepositoryRegistry::registerRepository($entityClass, $repositoryClass)`. Platform modules provide concrete repository implementations (MySQL, etc.); tests use in-memory repositories (`MemoryRepository`, `MemoryQueueItemRepository`).

### Task Execution Queue

Background work uses a queue system. Tasks implement `execute()` and yield progress (0-10000 base points). Lifecycle: `CREATED -> QUEUED -> IN_PROGRESS -> COMPLETED|FAILED|ABORTED`. The `HttpTaskExecutor` drives execution; platform modules provide the async HTTP trigger mechanism. `TaskExecutionConfig` carries queue name, priority, and context metadata.

### Bootstrap Chain

Platform modules must call `BootstrapComponent::init()` which chains:
1. `Infrastructure\BootstrapComponent::init()` — registers TimeProvider, GuidProvider, EventBus
2. `BusinessLogic\BootstrapComponent::init()` — registers Proxy, all business services, DTO registry

Platform modules then register their own implementations of abstract services (e.g., `Configuration`, `HttpClient`, repositories).

### Controllers

Controllers in `BusinessLogic/Controllers/` are thin wrappers that resolve services from `ServiceRegister` and delegate. They are meant to be called by platform-specific HTTP endpoint handlers, not used as framework controllers directly.

### DTO System

`FrontDtoFactory` maps string keys to DTO classes. DTOs are registered during bootstrap via `FrontDtoFactory::register()`. Front-facing DTOs extend `FrontDto` and support validation.

## Testing Patterns

- Tests extend `BaseTestWithServices` (business logic) or `BaseInfrastructureTestWithServices` (infrastructure), which set up `TestServiceRegister` with mock/test implementations.
- `TestHttpClient` captures HTTP calls for assertion; `TestShopConfiguration` provides test config.
- Setup uses `@before`/`@after` annotations (not `setUp`/`tearDown`) to chain parent initialization.
- PHPUnit 4.8 — no `void` return types on test methods, uses older assertion style.

## Key Constraints

> Full conventions are in `.ai-docs/coding-standards.md` — the authoritative style source. The most load-bearing constraints:

- **PHP 7.0 minimum** — no nullable types (`?Type`), no `void` return types, no typed properties. Use PHPDoc for type hints. `array()` syntax instead of `[]` is used throughout.
- **Platform agnostic** — this library must never depend on a specific e-commerce platform. Platform-specific behavior goes through abstract classes/interfaces (e.g., `Configuration`, `HttpClient`, `RepositoryInterface`).
- The `Configuration` class (`BusinessLogic\Configuration`) is abstract. Each platform module provides a concrete implementation that supplies webhook URLs, draft source, module version, e-commerce name/version.
