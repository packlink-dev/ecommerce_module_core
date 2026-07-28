#!/usr/bin/env bash
#
# publish-to-origin.sh - deliver changes from the current internal branch to the
# client repository (origin) WITHOUT any internal-only files.
#
# The script diffs the current branch against the merge-base with the target
# origin branch, strips internal-only paths, applies the result in a temporary
# worktree on top of origin/<target> and commits it as a single commit. The
# commit is then either pushed as a PR branch (default) or directly onto the
# target branch (--commit). The user's checkout is never touched.
#
# Usage:
#   bash docs/tools/publish-to-origin.sh [options]
#
# Options:
#   -t <branch>    Target origin branch (default: current branch without the
#                  "-internal" suffix, e.g. master-v2-internal -> master-v2)
#   -b <branch>    Publish branch name (default: publish/<source>-<timestamp>)
#   -m <message>   Commit message (default: "Deliver changes from <source>")
#   --commit       Push the commit directly to origin/<target> (asks for
#                  confirmation) instead of creating a PR branch
#   --dry-run      Show what would be published without pushing anything
#   -y, --yes      Skip the confirmation prompt in --commit mode
#   -h, --help     Show this help
#
# Environment:
#   PUBLISH_REMOTE   Remote to publish to (default: origin). Mainly for testing
#                    the script against a scratch remote.

set -euo pipefail

# Internal-only paths that must never reach the client repository.
# This list is the single source of truth - docs/publishing.md documents it.
# "docs" covers docs/specs and every other documentation subtree.
# ".ai-docs" is the pre-rename name, retained because older internal branches still
# carry it; dropping it would unprotect those branches.
INTERNAL_PATHS=(
    "docs"
    ".ai-docs"
    ".claude"
    ".github"
    "CLAUDE.md"
    "DESIGN.md"
    "LEARNINGS.md"
    ".phpunit.result.cache"
)

REMOTE="${PUBLISH_REMOTE:-origin}"

usage() {
    sed -n '3,29p' "$0" | sed 's/^# \{0,1\}//'
}

err() {
    echo "error: $*" >&2
    exit 1
}

TARGET=""
PUBLISH_BRANCH=""
MESSAGE=""
MODE="pr"
DRY_RUN=0
ASSUME_YES=0

while [ $# -gt 0 ]; do
    case "$1" in
        -t) TARGET="$2"; shift 2 ;;
        -b) PUBLISH_BRANCH="$2"; shift 2 ;;
        -m) MESSAGE="$2"; shift 2 ;;
        --commit) MODE="commit"; shift ;;
        --dry-run) DRY_RUN=1; shift ;;
        -y|--yes) ASSUME_YES=1; shift ;;
        -h|--help) usage; exit 0 ;;
        *) usage >&2; err "unknown option: $1" ;;
    esac
done

cd "$(git rev-parse --show-toplevel)"

SOURCE="$(git rev-parse --abbrev-ref HEAD)"
[ "$SOURCE" = "HEAD" ] && err "detached HEAD - check out the internal branch you want to publish from"

if [ -z "$TARGET" ]; then
    TARGET="${SOURCE%-internal}"
    [ "$TARGET" = "$SOURCE" ] && err "cannot derive target from '$SOURCE' (no -internal suffix); pass -t <branch>"
fi

[ -z "$PUBLISH_BRANCH" ] && PUBLISH_BRANCH="publish/${SOURCE}-$(date +%Y%m%d-%H%M%S)"
[ -z "$MESSAGE" ] && MESSAGE="Deliver changes from $SOURCE"

git rev-parse --verify -q "refs/heads/$PUBLISH_BRANCH" >/dev/null \
    && err "local branch '$PUBLISH_BRANCH' already exists; pick another name with -b"

echo "==> Fetching $REMOTE"
git fetch "$REMOTE"

git rev-parse --verify -q "refs/remotes/$REMOTE/$TARGET" >/dev/null \
    || err "branch '$TARGET' does not exist on $REMOTE; pass -t <branch>"

if [ -n "$(git status --porcelain)" ]; then
    echo "warning: working tree is dirty - only changes COMMITTED on '$SOURCE' are published." >&2
fi

# --- Build the filtered diff (merge-base semantics) -------------------------

EXCLUDES=()
for p in "${INTERNAL_PATHS[@]}"; do
    EXCLUDES+=(":(exclude)$p")
done

TMP_DIR="$(mktemp -d /tmp/publish-to-origin.XXXXXX)"
PATCH_FILE="$TMP_DIR/changes.patch"
WORKTREE_DIR="$TMP_DIR/worktree"

cleanup() {
    if [ -d "$WORKTREE_DIR" ]; then
        git worktree remove --force "$WORKTREE_DIR" >/dev/null 2>&1 || true
    fi
    git branch -D "$PUBLISH_BRANCH" >/dev/null 2>&1 || true
    rm -rf "$TMP_DIR"
}
trap cleanup EXIT

git diff --binary "$REMOTE/$TARGET...$SOURCE" -- . "${EXCLUDES[@]}" > "$PATCH_FILE"

if [ ! -s "$PATCH_FILE" ]; then
    echo "No publishable changes: '$SOURCE' only differs from $REMOTE/$TARGET in internal-only files."
    exit 0
fi

# --- Apply in an isolated worktree and commit --------------------------------

echo "==> Creating worktree on $REMOTE/$TARGET"
git worktree add -b "$PUBLISH_BRANCH" "$WORKTREE_DIR" "$REMOTE/$TARGET" >/dev/null

git -C "$WORKTREE_DIR" apply --binary --index "$PATCH_FILE"
git -C "$WORKTREE_DIR" commit -q -m "$MESSAGE"

# --- Leak guard: independently verify no internal path is in the commit ------

LEAKED=0
while IFS= read -r file; do
    for p in "${INTERNAL_PATHS[@]}"; do
        case "$file" in
            "$p"|"$p"/*) echo "LEAK BLOCKED: $file" >&2; LEAKED=1 ;;
        esac
    done
done < <(git -C "$WORKTREE_DIR" diff --name-only "$REMOTE/$TARGET"..HEAD)

[ "$LEAKED" -ne 0 ] && err "internal-only files ended up in the publish commit - aborting, nothing was pushed"

# --- Deliver ------------------------------------------------------------------

echo "==> Publish commit (source: $SOURCE, target: $REMOTE/$TARGET):"
git -C "$WORKTREE_DIR" show --stat --format='    %h %s%n' HEAD | sed 's/^/    /'

if [ "$DRY_RUN" -eq 1 ]; then
    echo "Dry run - nothing pushed."
    exit 0
fi

# Derive the "owner/repo" slug from the remote URL for PR links / gh.
REPO_SLUG="$(git remote get-url "$REMOTE" | sed -e 's/\.git$//' -e 's#^.*[:/]\([^/]\{1,\}/[^/]\{1,\}\)$#\1#')"

if [ "$MODE" = "commit" ]; then
    if [ "$ASSUME_YES" -ne 1 ]; then
        printf "Push this commit DIRECTLY to %s/%s? [y/N] " "$REMOTE" "$TARGET"
        read -r answer
        case "$answer" in
            y|Y|yes|YES) ;;
            *) echo "Aborted - nothing pushed."; exit 1 ;;
        esac
    fi
    git -C "$WORKTREE_DIR" push "$REMOTE" "HEAD:$TARGET"
    echo "==> Pushed directly to $REMOTE/$TARGET."
else
    git -C "$WORKTREE_DIR" push "$REMOTE" "HEAD:refs/heads/$PUBLISH_BRANCH"
    echo "==> Pushed branch '$PUBLISH_BRANCH' to $REMOTE."
    if command -v gh >/dev/null 2>&1; then
        gh pr create --repo "$REPO_SLUG" --base "$TARGET" --head "$PUBLISH_BRANCH" --title "$MESSAGE" --body ""
    else
        echo "Open the PR here:"
        echo "    https://github.com/$REPO_SLUG/compare/$TARGET...$PUBLISH_BRANCH?expand=1"
    fi
fi
