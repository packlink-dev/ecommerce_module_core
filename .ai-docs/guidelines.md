# Contribution & Change-Request Guidelines

> How work is planned and documented for `packlink/integration-core`. Read this together
> with [`coding-standards.md`](coding-standards.md) (how to write the code) and
> [`design.md`](design.md) (how the system is built).

---

## The `.ai-docs/` folder

| Path | Purpose |
|---|---|
| `.ai-docs/design.md` | System-wide architecture reference — layers, ORM, task queue, Packlink API proxy, business services, key flows, entity catalog, platform integration contract. |
| `.ai-docs/coding-standards.md` | Coding conventions derived from the codebase. |
| `.ai-docs/guidelines.md` | This file — the change-request workflow. |
| `.ai-docs/change_requests/<CR-ID>/` | One folder per change request (see below). |

## Change-request structure

Every change request gets its own folder under `.ai-docs/change_requests/`, named with the
ticket ID plus a short slug, e.g. `CR-SET-62b-labels`. The folder contains two documents,
following the existing `CR-SET-62b-labels` example:

### `spec.md` — *what & why*

- Overview of the change and the problem it solves.
- The concrete classes, DTOs, and interfaces to add or change — with **namespaces**, **fields**, and **method signatures**.
- "Files Summary" tables splitting **New Files** from **Modified Files**.
- Rationale notes for non-obvious decisions (e.g. why an existing type is wrapped rather than replaced).

### `design.md` — *how*

- Ordered **implementation tasks**, each with an effort estimate and explicit dependencies.
- A **task-dependency graph** showing what can run in parallel.
- A **files-changed-per-task** table.

## Workflow

1. **Before designing a CR**, read `design.md` to understand the architecture and the platform-integration contract.
2. **Write the CR docs** (`spec.md`, then `design.md`) in a new `change_requests/<CR-ID>/` folder.
3. **Before implementing**, read `coding-standards.md` and the CR's `spec.md` + `design.md`.
4. **Implement** following the task order in `design.md`.
5. **After implementing:**
   - Run the test suite:
     ```bash
     php vendor/bin/phpunit --configuration phpunit.xml
     ```
   - Confirm PHP 7.0 compatibility (no `[]`, nullable types, typed properties, or return types — see `coding-standards.md`).
   - If the change alters the architecture, **update `design.md`** to keep it in sync.
