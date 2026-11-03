# Improvement roadmap — Phase 2.6 plan

Written 2026-11-03 as the required Phase 2 step 6 deliverable ("produce a plan for Phases 3-5, with risks — SHOW ME THE PLAN -> APPROVAL GATE") before any Phase 3 work starts, per `PROMPT.md`. This gate was skipped once already on repo #2 earlier in this session and is being followed carefully from here on (see `be-chatty`'s `IMPROVEMENT_ROADMAP.md` for the same pattern).

## Why this plan is short

`photo-gallery-api` is **not** in the state `be-chatty` was in. There is no missing-business-logic gap, no broken build, no broken lint, no broken tests, no lockfile desync, no hardcoded secrets. Every CI gate was independently re-run locally during Phase 2 and is green except one documented dependency CVE. This plan is a small, mostly-mechanical hygiene pass, not a recovery effort.

## What this plan does

1. **Fix the one real security finding.** `composer update symfony/mime` (or equivalent) to resolve CVE-2026-45070, within the existing `laravel/framework` constraint — no `composer.json` range change expected. Verify via `composer audit` locally and in CI's own Docker-based gate afterward.
2. **Triage the 9 open Renovate PRs, individually — never batch-merged**, following the same method used for `be-chatty` (checked each PR's actual diff, not just its title):
   - Merge the 4 safe ones directly: `#40` (guzzle patch, in-range), `#39`/`#38`/`#34` (Docker digest-only bumps, no semantic version change at all).
   - **Do not merge the 5 major/minor-range bumps in this pass**: `#37` (laravel/framework v12→v13), `#28` (guzzle v7→v8), `#32` (PHP Docker tag 8.4→8.5), `#20` (phpunit v11→v13), `#41` (MySQL Docker tag 8.4→26.10). Each is a real compatibility-risk upgrade and falls under CLAUDE.md Approval Gate #5 ("a major version upgrade that breaks backward API compatibility") — they'll be left open, documented in `TECHNICAL_DEBT.md`, for your separate review/approval rather than decided here.
3. **Leave the 3 P3 cleanup items undone, as documented debt, not silently fixed or silently ignored**: the stubbed/unregistered `ImagePolicy`, the unused frontend scaffolding, and the misnamed migration file. None carry security or correctness risk; all three are listed in `TECHNICAL_DEBT.md` for your prioritization, matching CLAUDE.md's instruction not to over-engineer or refactor beyond what's asked.
4. **Raise the default-branch rename (`master` -> `main`) as a separate question once the above is done** — same Gate #4 pattern as `be-chatty`, not decided inside this plan.

## What this plan explicitly does NOT do

- Does not touch `ImagePolicy`, the frontend scaffolding, the migration filename, or the `test`-branch workflow-drift note — all P3, all your call, not assumed.
- Does not merge any of the 5 flagged major-version PRs without your explicit review of each.
- Does not add a license, rewrite the README, or otherwise go beyond the hygiene/security scope — the existing README and docs are already accurate and don't need rewriting the way `be-chatty`'s course-syllabus README did.

## Risks

- **Low overall.** The `symfony/mime` fix is a transitive-dependency patch bump with no `composer.json` range change expected — low risk, verified by re-running the full local Docker CI replica (build, test, lint, audit) after the change, same as every fix verified for `be-chatty`.
- The 4 safe PR merges are lockfile/digest-only — the same risk profile as the ~14 safe merges already done for `be-chatty` in this session.
- The main risk this plan avoids is **merging a major-version bump without review** — specifically the MySQL Docker tag jump (8.4→26.10), which is a real database engine upgrade disguised by Renovate as a routine tag bump, and the Laravel v13 upgrade, which could carry breaking API changes per Laravel's own upgrade guide (not yet read in detail, since this PR is being deferred rather than merged).

## Approval gate

This is the Phase 2 plan for `photo-gallery-api`, submitted per CLAUDE.md Approval Gate #10. Phase 3 work (the `symfony/mime` fix and the 4 safe PR merges) will not start until this is approved.
