---
description: Scaffold a new docs/change-requests/<CR-ID> folder (spec.md, design.md, tasks.md, optional requirements/)
---

Scaffold a new change-request folder under `docs/change-requests/`, following the structure
documented in `docs/guidelines.md`.

Argument: `$ARGUMENTS` — the ticket ID plus a short slug, e.g. `CR-SET-70-carrier-webhooks`. If
not provided, ask for it before doing anything else.

Steps:

1. Read `docs/guidelines.md` for the current convention if you haven't already this session.
2. Create `docs/change-requests/<CR-ID-slug>/`.
3. If the user has source material to attach (a ticket export, a requirements PDF, mockup
   images), create `requirements/` first and put it there. If it's a PDF, offer to run
   `docs/tools/pdf_to_md.py` to extract it to `requirements/requirements.md` +
   `requirements/images/`.
4. Create `spec.md` — *what & why*. Do NOT invent requirements: base it strictly on what the
   user has told you or what's in `requirements/`. Ask clarifying questions for anything
   ambiguous rather than guessing. Structure:
   - Overview of the change and the problem it solves.
   - The concrete classes/DTOs/interfaces to add or change, with namespaces, fields, and method
     signatures.
   - New/Modified file tables.
   - Rationale notes for non-obvious decisions.
5. Create `design.md` — *how it fits*. Architecture recap of the relevant existing flow,
   components & responsibilities, deltas & rationale. For a small CR this can be a short
   paragraph — don't pad it out. Point back to `spec.md` for anything already covered there.
6. Create `tasks.md` — *ordered work*. Numbered implementation tasks with effort estimates and
   explicit dependencies, a task-dependency graph, and a files-changed-per-task table.
7. Follow `docs/coding-standard.md` conventions in every code snippet you write into these
   docs (PHP 7.0 — no nullable types, no `void` returns, no typed properties; `array()` syntax).
8. Use `docs/change-requests/2026-06-cr66-customs-support/` as the structural reference for
   formatting and level of detail — don't copy its content, just its shape.
9. Stop and show the user the drafted files before considering the task done — do not treat CR
   docs as final without their review, since they encode decisions only they can validate.
