#!/bin/sh
# Brings main-backup's work into main without the dev-only files, on a new sync/<date-time>
# branch for a pull request into main (dev-docs/handoff.md, "Branches and sync").
# Never merge main into main-backup: the dev-only files are deleted on main.
set -eu

# Kept on main-backup, never on main. A new dev-only doc goes in dev-docs/, which needs no entry here;
# any other new dev-only file goes here in the same commit.
DEV_ONLY='.claude .impeccable CLAUDE.md PRODUCT.md DESIGN.md dev-docs scripts/sync-main.sh'

# Mentions of dev tooling or internal docs that must not reach a file main keeps.
LEAKS='dev-docs|CLAUDE\.md|\.claude|[Ii]mpeccable|[Pp]onytail|Claude Code|progress\.md|handoff\.md|implementation-plan|clarification[- ]question|\(question [0-9]+\)|client-discussion|kickoff\.md|breach-report-template|production-access-log|answers-change-plan|neek-call|PRODUCT\.md|DESIGN\.md|main-backup|plan section'

# A function, so the shell has read the whole file before git switch deletes it.
sync_main() {
    if ! git diff --quiet || ! git diff --cached --quiet; then
        echo 'Commit or stash your changes first.' >&2
        exit 1
    fi

    branch="sync/$(date +%Y%m%d-%H%M)"
    git fetch origin
    git switch -c "$branch" origin/main

    # A dev-only file changed on main-backup conflicts with its deletion on main; git rm settles it.
    # Any other conflict stops the script at git commit, to be resolved by hand.
    if ! git merge --no-ff --no-commit origin/main-backup; then
        git rev-parse -q --verify MERGE_HEAD >/dev/null || exit 1
    fi
    if ! git rev-parse -q --verify MERGE_HEAD >/dev/null; then
        echo 'main already has everything from main-backup.'
        git switch -q -
        git branch -q -D "$branch"
        exit 0
    fi

    # -f: the merge staged changes to these files; the tree was clean, so nothing else is lost.
    git rm -r -q -f --ignore-unmatch $DEV_ONLY  # unquoted on purpose: one path per word
    git commit -q -m 'chore(handover): sync main with main-backup, without the dev-only files'

    if git grep -n -I -E "$LEAKS" HEAD -- . ':!composer.lock' ':!package-lock.json'; then
        echo "The lines above would reach main. Fix them on main-backup, delete $branch and run again." >&2
        exit 1
    fi
    echo "Ready on $branch. Push it (git push -u origin HEAD), open a pull request into main"
    echo '(gh pr create --base main) and merge it with "Create a merge commit".'
}

sync_main
