# Publishing to the Client Repository (origin)

## The two-remote model

| Remote | Repository | Purpose |
|---|---|---|
| `origin` | `packlink-dev/ecommerce_module_core` | **Client repository.** Receives only production code. |
| `internal` | `logeecom-dev/packlink-core-logeeecom-dev` | Logeecom development repository. Receives everything, including AI docs. |

Development happens on internal branches (suffix `-internal`, e.g. `master-v2-internal`),
which track the corresponding origin branch plus internal-only content. **Never merge or
push an internal branch to `origin` directly** — that would leak internal files into the
client repository. All delivery to origin goes through the publish script.

## Internal-only paths

These paths must never reach the client repository. The canonical, machine-enforced list
lives in `.ai-docs/tools/publish-to-origin.sh` (`INTERNAL_PATHS`); keep this table in sync
when it changes:

- `.ai-docs/` — change requests, standards, architecture docs, tools (including the publish script itself)
- `.claude/` — Claude Code commands and settings
- `.github/` — internal PR templates
- `CLAUDE.md` — AI assistant instructions
- `DESIGN.md` — living architecture record
- `LEARNINGS.md` — continuous-learning log
- `docs/specs/` — per-feature spec-driven-development documents
- `.phpunit.result.cache` — test cache

These are also listed in `.git/info/exclude` (local-only ignore, not pushed anywhere) so
untracked internal files never show up in `git status` / `git add -A` by accident.

## The publish script

`.ai-docs/tools/publish-to-origin.sh` takes everything committed on the **current branch**
since its merge-base with the target origin branch, strips the internal-only paths, and
commits the remainder as a **single squashed commit** on top of `origin/<target>` in a
temporary worktree (your checkout is never touched). It then delivers that commit.

```bash
# Preview what would be published (always do this first)
bash .ai-docs/tools/publish-to-origin.sh --dry-run

# Default: push a PR branch to origin and print the PR link
bash .ai-docs/tools/publish-to-origin.sh -b CR-SET-67 -m "Add customs support (CR-SET-67)"

# Direct commit onto the target branch (asks for confirmation; -y skips it)
bash .ai-docs/tools/publish-to-origin.sh --commit -m "Release version 4.2.5"

# Publish to a target that can't be derived from the branch name
bash .ai-docs/tools/publish-to-origin.sh -t master-v2
```

Options:

| Option | Meaning | Default |
|---|---|---|
| `-t <branch>` | Target origin branch | current branch minus the `-internal` suffix |
| `-b <branch>` | Name of the PR branch pushed to origin | `publish/<source>-<timestamp>` |
| `-m <message>` | Commit message on origin | `Deliver changes from <source>` |
| `--commit` | Push directly onto `origin/<target>` instead of a PR branch | PR mode |
| `--dry-run` | Show the resulting commit, push nothing | off |
| `-y` / `--yes` | Skip the confirmation prompt in `--commit` mode | ask |

If `gh` is installed the script opens the PR itself; otherwise it prints the GitHub
compare URL to open the PR manually.

## Safety properties

- **Filtered at the diff level** — internal paths are excluded via git pathspecs when the
  patch is generated.
- **Independent leak guard** — after committing, the script re-diffs the publish commit
  against `origin/<target>` and aborts (before any push) if any internal path slipped in.
- **Isolated worktree** — the commit is built in a temporary `git worktree`; your working
  tree, index, and current branch are never modified. Worktree, temp branch, and patch
  file are cleaned up on exit, including on failure.
- **Committed changes only** — uncommitted (dirty) changes are never published; the script
  warns if the tree is dirty.
- **No force pushes** — in `--commit` mode the push is a plain fast-forward; if origin
  moved since the fetch, the push fails cleanly. Re-run the script to rebuild on the new
  tip.

## Typical flow

1. Finish work on the internal branch (e.g. `master-v2-internal`); everything to be
   delivered is committed.
2. `bash .ai-docs/tools/publish-to-origin.sh --dry-run` — check the file list contains
   only production code.
3. `bash .ai-docs/tools/publish-to-origin.sh -b <ticket-id> -m "<message>"` — push the PR
   branch and open the PR against the target branch.
4. After the client-side PR is merged: `git fetch origin` and merge `origin/<target>` back
   into the internal branch so the branches stay in sync
   (`git merge origin/master-v2` on `master-v2-internal`).

## When the target branch has moved

The script always fetches first and builds on the fresh `origin/<target>` tip, so simply
re-running it picks up the new state. If the same logical change was already merged on
origin (e.g. via a client-side PR), the merge-base diff shrinks accordingly — after the
sync-back merge in step 4 the script reports "no publishable changes".
