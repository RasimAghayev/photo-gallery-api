#!/usr/bin/env bash
# Scans the currently staged changes for secrets before a commit lands.
#
# Why a snapshot instead of scanning the working tree or bind-mounting .git:
# gitleaks needs to see exactly what is about to be committed (staged
# content), not whatever is sitting unstaged in the working tree. Bind-mounting
# the repo's .git directory into a container also breaks when this repo is
# checked out as a `git worktree` (the default for this program's agents,
# PROMPT.md R4): a worktree's .git is a *pointer file* containing an absolute
# host path, which a Linux container can never resolve, silently making a
# direct mount scan report 0 bytes / "no leaks found" even with a real secret
# staged. Snapshotting the staged blobs into a plain scratch directory (under
# this repo's own top level, never /tmp) sidesteps both problems identically
# whether this is a normal clone or a worktree.
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
scratch_dir="$repo_root/.git-secret-scan-scratch"
rm -rf "$scratch_dir"
mkdir -p "$scratch_dir"
trap 'rm -rf "$scratch_dir"' EXIT

staged_files="$(git diff --cached --name-only --diff-filter=ACMR)"
if [ -z "$staged_files" ]; then
  exit 0
fi

while IFS= read -r f; do
  [ -z "$f" ] && continue
  dest="$scratch_dir/$f"
  mkdir -p "$(dirname "$dest")"
  git show ":$f" > "$dest" 2>/dev/null || true
done <<< "$staged_files"

run_gitleaks() {
  if command -v gitleaks >/dev/null 2>&1; then
    gitleaks detect --no-git --source="$scratch_dir" -v
    return $?
  fi

  if command -v docker >/dev/null 2>&1; then
    # MSYS_NO_PATHCONV=1 stops Git-Bash-on-Windows from mangling the /scratch
    # mount path into something like "C:/Program Files/Git/scratch".
    MSYS_NO_PATHCONV=1 docker run --rm \
      -v "$scratch_dir:/scratch" \
      zricethezav/gitleaks:latest \
      detect --no-git --source=/scratch -v
    return $?
  fi

  echo "secret-scan: neither 'gitleaks' nor 'docker' is available on PATH." >&2
  echo "secret-scan: skipping local pre-commit scan for this commit." >&2
  echo "secret-scan: CI's own secret-scan job remains the real backstop." >&2
  return 0
}

if ! run_gitleaks; then
  echo "" >&2
  echo "secret-scan: gitleaks found a potential secret in staged changes." >&2
  echo "secret-scan: commit blocked. Remove the secret (or use \`git reset\`" >&2
  echo "secret-scan: to unstage it) before committing again." >&2
  exit 1
fi

exit 0
