# Improvement roadmap — Phase 2.6 plan

Written 2026-11-03 as the required Phase 2 step 6 deliverable ("produce a plan for Phases 3-5, with risks — SHOW ME THE PLAN -> APPROVAL GATE") before any Phase 3 work starts, per `PROMPT.md`. This gate was skipped once already on repo #2 earlier in this session and is being followed carefully from here on (see `be-chatty`'s `IMPROVEMENT_ROADMAP.md` for the same pattern).

## Why this plan is short

`photo-gallery-api` is **not** in the state `be-chatty` was in. There is no missing-business-logic gap, no broken build, no broken lint, no broken tests, no lockfile desync, no hardcoded secrets. Every CI gate was independently re-run locally during Phase 2 and is green except one documented dependency CVE. This plan is a small, mostly-mechanical hygiene pass, not a recovery effort.

## What this plan does

1. **Fix the one real security finding.** `composer update symfony/mime` (or equivalent) to resolve CVE-2026-45070, within the existing `laravel/framework` constraint — no `composer.json` range change expected. Verify via `composer audit` locally and in CI's own Docker-based gate afterward.
2. **Triage the 9 open Renovate PRs, individually — never batch-merged**, following the same method used for `be-chatty` (checked each PR's actual diff, not just its title):
   - Merge the 4 safe ones directly: `#40` (guzzle patch, in-range), `#39`/`#38`/`#34` (Docker digest-only bumps, no semantic version change at all).
   - **Do not merge the 5 major/minor-range bumps in this pass**: `#37` (laravel/framework v12→v13), `#28` (guzzle v7→v8), `#32` (PHP Docker tag 8.4→8.5), `#20` (phpunit v11→v13), `#41` (MySQL Docker tag 8.4→26.10). Each is a real compatibility-risk upgrade and falls under CLAUDE.md Approval Gate #5 ("a major version upgrade that breaks backward API compatibility") — they'll be left open, documented in `TECHNICAL_DEBT.md`, for your separate review/approval rather than decided here. *(`#32` and `#20` were separately approved and merged earlier in this session. `#37` approved and merged 2026-11-03 — its lockfile was stale and needed regenerating before the real CI gate went green; see `TECHNICAL_DEBT.md` #2 for the full verification trail. `#28` and `#41` remain open, awaiting your decision.)*
3. **Leave the 3 P3 cleanup items undone, as documented debt, not silently fixed or silently ignored**: the stubbed/unregistered `ImagePolicy`, the unused frontend scaffolding, and the misnamed migration file. None carry security or correctness risk; all three are listed in `TECHNICAL_DEBT.md` for your prioritization, matching CLAUDE.md's instruction not to over-engineer or refactor beyond what's asked.
4. **Raise the default-branch rename (`master` -> `main`) as a separate question once the above is done** — same Gate #4 pattern as `be-chatty`, not decided inside this plan. *(Completed 2026-11-03 — approved and executed; see `TECHNICAL_DEBT.md` #3.)*

## What this plan explicitly does NOT do

- Does not touch `ImagePolicy`, the frontend scaffolding, the migration filename, or the `test`-branch workflow-drift note — all P3, all your call, not assumed.
- Does not merge any of the 5 flagged major-version PRs without your explicit review of each.
- Does not add a license, rewrite the README, or otherwise go beyond the hygiene/security scope — the existing README and docs are already accurate and don't need rewriting the way `be-chatty`'s course-syllabus README did.

## Risks

- **Low overall.** The `symfony/mime` fix is a transitive-dependency patch bump with no `composer.json` range change expected — low risk, verified by re-running the full local Docker CI replica (build, test, lint, audit) after the change, same as every fix verified for `be-chatty`.
- The 4 safe PR merges are lockfile/digest-only — the same risk profile as the ~14 safe merges already done for `be-chatty` in this session.
- The main risk this plan avoids is **merging a major-version bump without review** — specifically the MySQL Docker tag jump (8.4→26.10), still open and deferred. The Laravel v13 upgrade's own official upgrade guide *has* since been read in full (both High Impact items and every Medium/Low item) as part of merging `#37` — see `TECHNICAL_DEBT.md` #11 for the one finding (a deprecated-but-working CSRF middleware alias) and confirmation that nothing else in the guide applies to this codebase.

## Approval gate

This is the Phase 2 plan for `photo-gallery-api`, submitted per CLAUDE.md Approval Gate #10. Phase 3 work (the `symfony/mime` fix and the 4 safe PR merges) will not start until this is approved.

**Status: approved and executed 2026-11-03.** See `TECHNICAL_DEBT.md` items #1-#3.

---

# Phase 4 plan — modernization and independence

Written 2026-11-03, after Phase 3 closed out clean. Per `PROMPT.md` Phase 4's 7-item checklist, checked against this repo's actual state (reconnaissance done via `grep`/`find`/reading `config/*.php`, `routes/api.php`, `composer.json` — not assumed).

## Checklist walk-through

| PROMPT.md item | Current state | Action |
|---|---|---|
| 1. Dependency upgrades | 5 majors still open (`#37`/`#28`/`#32`/`#20`/`#41`). Re-checked CI today: `#41` (MySQL 26.x) and `#32` (PHP 8.5) now show **CI green**; `#37` (Laravel v13), `#28` (guzzle v8), `#20` (phpunit v13) still **FAIL**. | **No merges.** CI-green doesn't clear Gate #5 by itself — the test suite runs against in-memory SQLite, so a green run on the MySQL-tag PR says nothing about real MySQL 26 compatibility, and a PHP minor-tag bump still warrants its own read of the PHP 8.5 changelog before touching a runtime image. All 5 stay open, unchanged, for your separate review. |
| 2. Bugs/security/performance | Walked the full CLAUDE.md checklist: auth (Sanctum) ✓, input validation (FormRequest) ✓, no raw SQL (Eloquent only) ✓, no CSRF surface (token API, no cookies) ✓, secrets clean ✓, rate limiting **present** (`RateLimiter::for('api', ...)`, 60/min) ✓, file-upload validated (mime+size) ✓. CORS is `allowed_origins: ['*']` but `supports_credentials: false` — safe for a Bearer-token API with no cookie auth. | **Nothing to fix.** No new P0-P2 finding. |
| 3. Docker | Already assessed best-practice in Phase 2/3 (multi-stage, digest-pinned, non-root, healthcheck, resource limits). | **Nothing to add.** |
| 4. Dual-mode independence | `.env.example` ✓. **No `Makefile`** (README documents raw `docker compose` commands only). **No `/health/live` or `/health/ready`** route exists. Config is already env-driven, nothing baked into the image ✓. **No `openapi.yaml`** — only prose `docs/api.md` (accurate, but not machine-readable). No optional external service to stub (MySQL is the only dependency and is already enforced via Compose `:?`). | **Add:** a thin `Makefile` wrapping the existing documented commands; `/health/live` + `/health/ready` routes; `openapi.yaml` generated from the already-accurate `docs/api.md` contract (6 endpoints, not invented). |
| 5. Observability | No APM/error-tracking/metrics package installed. Default Laravel `stack` log channel (writes to `storage/logs`, captured by Docker's log driver). No production deployment or incident history exists for this repo. | **Add the floor only** (liveness/readiness — covered by item 4). **Do not add** Sentry/Prometheus/OpenTelemetry: new dependency, new secret (DSN), ongoing cost, with no real failure history to diagnose — exactly the over-engineering CLAUDE.md warns against for a solo reference-implementation repo. Documented as optional future work, not silently skipped. |
| 6. Quality tooling | Pint (formatter/linter) ✓, `composer audit` ✓, GitGuardian secret scanning already runs as a PR check (confirmed in CI status output) ✓. **No static analyzer** (PHPStan/Larastan) installed. No pre-commit hooks. | **Add:** Larastan (PHPStan for Laravel), pragmatic level, wired into CI as a new gate. **Skip pre-commit hooks** — CI already enforces every gate on push/PR for a solo-maintained repo; a local hook would be redundant, not load-bearing. |
| 7. CI/CD | Already comprehensive (7 gates, Docker-only, SHA-pinned checkout). | **Extend:** add one new gate for Larastan, mirroring the existing `pint --test` step. |

## What this plan does

1. Add `/health/live` (process is up) and `/health/ready` (DB connection works) routes — unauthenticated, outside the `v1` API prefix, standard for K8s/orchestration probes.
2. Add a `Makefile` with `make up` / `make down` / `make test` / `make lint` / `make audit` — thin wrappers around the exact `docker compose --env-file var/.env ...` commands already documented in the README, no new behavior.
3. Add `openapi.yaml` at the repo root, describing the 6 existing endpoints (+ the 2 new health endpoints), generated from `docs/api.md`'s already-verified request/response shapes — a machine-readable mirror of the existing prose doc, not a replacement for it.
4. Add `larastan/larastan` as a dev dependency, a baseline `phpstan.neon`, and a new CI gate (`vendor/bin/phpstan analyse`). Fix whatever it flags only if the fix is safe/mechanical (e.g. a missing return type); anything requiring a real behavior change gets documented in `TECHNICAL_DEBT.md` instead of silently patched.
5. Update `README.md` and `docs/REPOSITORY_AUDIT.md` to reflect the new commands/endpoints/CI gate once done.

## What this plan explicitly does NOT do

- Does not merge any of the 5 deferred major-version PRs — unchanged from the Phase 2.6 decision, still your call.
- Does not add Sentry/Prometheus/OpenTelemetry or any other observability *infrastructure* — no production deployment exists to justify it yet.
- Does not add pre-commit hooks, a `MODE=standalone` stub (no optional external service exists to stub), or touch the 3 already-deferred P3 cleanup items (`ImagePolicy`, frontend scaffolding, migration filename).
- Does not rewrite `docs/api.md` — `openapi.yaml` is additive, generated from it.

## Risks

- **Low.** Every addition is new, additive surface (new routes, new file, new dev-only dependency, new CI step) — nothing existing is modified except two docs files reflecting the new state. The full local Docker CI replica will be re-run after each addition, same discipline as Phase 3.
- The one item with any judgment call is Larastan's strictness level — starting at a low/pragmatic level (not level 9) to avoid a wall of pre-existing findings unrelated to this pass; can be raised later if you want stricter analysis.

## Approval gate

Submitting this Phase 4 plan before implementing, consistent with the Phase 2.6 precedent (this isn't itself a numbered CLAUDE.md gate, but touches new endpoints/files/dependencies and benefits from the same checkpoint).

**Status: approved and executed 2026-11-03.**

- `/health/live` + `/health/ready` added (`app/Http/Controllers/HealthController.php`, registered in `RouteServiceProvider`) — manually smoke-tested with the real stack up: both return `200 {"status":"ok"}`; stopping the `mysql` container made `/health/ready` correctly return `503` while `/health/live` stayed `200`; restarting `mysql` recovered it.
- `Makefile` added (`up`/`down`/`build`/`test`/`lint`/`audit`/`analyse`/`migrate`/`token`/`logs`) — every target actually run and verified, not just written.
- `openapi.yaml` added, validated against the OpenAPI 3.0.3 schema with `openapi-spec-validator` (passed clean).
- `larastan/larastan` added as a dev dependency (`composer.json`/`composer.lock`, lockfile-only otherwise — `guzzlehttp/guzzle` moved 7.16.0→7.16.1 as a transitive in-range patch, not a range change), `phpstan.neon` at level 5, wired into `.github/workflows/ci.yml` as a new gate. Initial run found 18 pre-existing findings:
  - **Fixed (mechanical, zero behavior change):** `Authenticate::redirectTo()` missing `return null;`; `ImageResource`/`ImageDetailResource` missing `@mixin Image` PHPDoc so Larastan resolves the magic-proxied model properties they already read at runtime. 9 findings resolved this way.
  - **Baselined, not touched:** the 7 `ImagePolicy` missing-return findings (dead, unregistered code — `TECHNICAL_DEBT.md` #4, already explicitly deferred) and 2 `User` model PHPDoc-covariance warnings (a stock Laravel 12 generated-stub artifact, not an app bug). Captured in `phpstan-baseline.neon` with a comment explaining why, so the new CI gate is meaningful for new code without silently masking either.
- Full local Docker CI replica re-run clean after every change: compose config, test-image build, **24/24 tests**, **69-file lint clean**, **0 audit findings**, **Larastan 0 errors**, app-image build, db-image build.
- `README.md` updated: new endpoints, new `make` commands, `openapi.yaml` reference, new static-analysis gate.
- Deliberately not done, as planned: none of the 5 deferred major-version PRs merged; no observability infrastructure (Sentry/Prometheus/OpenTelemetry) added; no pre-commit hooks added.
