# Contribution & Change-Request Guidelines

> How work is planned and documented for `packlink/integration-core`. Read this together
> with [`coding-standards.md`](coding-standards.md) (how to write the code) and
> [`architecture.md`](architecture.md) (how the system is built).

---

## The `.ai-docs/` folder

| Path | Purpose |
|---|---|
| `.ai-docs/architecture.md` | System-wide architecture reference — layers, ORM, task queue, Packlink API proxy, business services, key flows, entity catalog, platform integration contract. |
| `.ai-docs/coding-standards.md` | Coding conventions derived from the codebase. |
| `.ai-docs/guidelines.md` | This file — the change-request workflow. |
| `.ai-docs/tools/` | Reusable scripts for authoring CR docs (e.g. `pdf_to_md.py` to convert a source requirements PDF to Markdown + extracted images). Not part of the documentation itself. |
| `.ai-docs/change_requests/<CR-ID>/` | One folder per change request (see below). |

## Change-request structure

Every change request gets its own folder under `.ai-docs/change_requests/`, named with the
ticket ID plus a short slug, e.g. `CR-SET-66-Customs-support`. The folder contains three
documents, following the `CR-SET-66-Customs-support` example:

### `spec.md` — *what & why*

- Overview of the change and the problem it solves.
- The concrete classes, DTOs, and interfaces to add or change — with **namespaces**, **fields**, and **method signatures**.
- "Files Summary" tables splitting **New Files** from **Modified Files**.
- Rationale notes for non-obvious decisions (e.g. why an existing type is wrapped rather than replaced).

### `design.md` — *how it fits*

- Architecture recap of the relevant existing flow.
- Components & responsibilities (a table of files/classes touched and their status).
- Deltas & rationale — what's changing structurally and why, including any non-obvious
  trade-offs or observations worth recording.
- For a small, self-contained CR this can be a short paragraph pointing back to `spec.md`'s
  rationale rather than a full write-up — it doesn't need to be padded out.

### `tasks.md` — *ordered work*

- Ordered **implementation tasks**, each with an effort estimate and explicit dependencies.
- A **task-dependency graph** showing what can run in parallel.
- A **files-changed-per-task** table.

### `requirements/` *(optional)*

- Raw source material the CR was derived from: the original ticket export, a requirements PDF,
  mockup images, or a `requirements.md` extracted from them (see `.ai-docs/tools/pdf_to_md.py`).
- Kept separate from `spec.md`/`design.md`/`tasks.md` so it's clear what's *input* (source
  material, not to be edited) versus *output* (the derived, maintained docs).

## Workflow

1. **Before designing a CR**, read `architecture.md` to understand the architecture and the platform-integration contract.
2. **Write the CR docs** (`spec.md`, then `design.md`, then `tasks.md`) in a new `change_requests/<CR-ID>/` folder. Drop any source material under `requirements/` first if you have it.
3. **Before implementing**, read `coding-standards.md` and the CR's `spec.md` + `design.md` + `tasks.md`.
4. **Implement** following the task order in `tasks.md`.
5. **After implementing:**
   - Run the test suite:
     ```bash
     php vendor/bin/phpunit --configuration phpunit.xml
     ```
   - Confirm PHP 7.0 compatibility — no nullable types (`?Type`), no `void` return types, no
     typed properties (see `coding-standards.md`; plain return type declarations like `: array`
     are fine, they're valid since PHP 7.0). `array()` syntax instead of `[]` is a house style
     choice, not a version requirement.
   - If the change alters the architecture, **update `architecture.md`** to keep it in sync.
