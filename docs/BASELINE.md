# Container / quality-gate baseline

This repo's Phase 5 (container-engineer) work predates this program's R6
evidence standard (ad-hoc, board-tracked as `done*`), and never produced this
file. The table below is the first formal baseline, written by the Phase 6
(`upgrader`) agent as the required pre-upgrade "before" state, by re-running
every gate the repo's own `.github/workflows/ci.yml` / `Makefile` defines —
not a new gate design, a faithful replica of the existing CI job.

## 2026-11-05 — Phase 6 pre-upgrade baseline (re-verification)

Run from `projects/worktrees/photo-gallery-api` (branch `phase-6/dependency-upgrade`,
based on `main`@`8f5d9ff`), `COMPOSE_PROJECT_NAME=photo-gallery-api-p6`,
`APP_PORT=20060` (see `projects/info/state/ports.md`), against the
**unmodified** `main` dependency tree (no bump applied yet).

| Gate (CI step) | Command | Result | Exit |
|---|---|---|---|
| Config validate | `docker compose --env-file var/.env config --quiet` | OK | 0 |
| Build test image | `docker compose --env-file var/.env build test` | Built, fully cached — confirms `php:8.5-apache@sha256:4cc63b3d...` / `composer:2@sha256:5190d9b4...` pins unchanged since this image was last built | 0 |
| Run test suite | `docker compose --env-file var/.env --profile test run --rm test` | PHPUnit 13.4.6, PHP 8.5.12 — **33/33 tests, 77 assertions, 1 deprecation** (the already-documented `PDO::MYSQL_ATTR_SSL_CA` deprecation, `docs/TECHNICAL_DEBT.md` #10 — pre-existing, not new) | 0 |
| Lint (Pint) | `... --entrypoint ./vendor/bin/pint test --test` | PASS, 70 files | 0 |
| **Audit** | `... --entrypoint composer test audit --no-interaction --abandoned=report` | **FAIL — 7 advisories across 4 packages**: `symfony/http-foundation` (locked v8.1.10, ×4 advisories, no CVE ID each), `symfony/http-kernel` (locked v8.1.10, 1 advisory, no CVE ID), `symfony/routing` (locked v8.1.10, **CVE-2026-118516**), `symfony/yaml` (locked v8.1.9, 1 advisory, GHSA-hwfg-qwvh-2wfx, no CVE ID). All four advisories' affected ranges end at `<8.1.11` — fixed by a `v8.1.11` bump, confirmed live-current on Packagist as of this run. This is the **pre-existing, already-known baseline failure** that blocks PR #45 and is this phase's priority fix (see "Versions" below) — reported dates on the advisories are 2026-11-04, i.e. disclosed *after* this repo's ad-hoc Phase 5 work and *after* the Phase 2 snapshot (2026-11-04, which recorded a clean audit) ran, not a regression introduced by anything in this repo. | **1** |
| Static analysis (Larastan) | `... --entrypoint ./vendor/bin/phpstan test analyse --memory-limit=512M` | OK, 0 errors | 0 |
| Build app image | `docker compose --env-file var/.env build app` | Built (one transient `exit 137` on the first attempt — Docker Desktop hiccup, unrelated to any file change; retry succeeded, daemon was never down per `docker info`) | 0 |
| Build db image | `docker compose --env-file var/.env build mysql` | Built, fully cached — `mysql:26.10@sha256:3819b2b2...` pin confirmed still the live-latest `26.x` tag (checked via Docker Hub API against all current `26.*` tags) | 0 |

**Conclusion: the ad-hoc Phase 5 baseline holds, with exactly one known,
already-documented exception** — `composer audit` fails on 4 transitive
Symfony packages due to advisories disclosed 2026-11-04 (after Phase 5's own
work and after the Phase 2 dependency snapshot), independently reproducing
the exact finding Phase 4's `git-steward` already recorded as the reason PR
#45 (`chore: add gitleaks pre-commit secret-scan hook`) stays unmerged. Every
other gate — tests, lint, static analysis, both image builds, the Docker base
image digests — is unchanged and green. No regression to attribute to this
baseline re-check; the one failure is the documented, pre-existing one this
phase exists to fix.

## 2026-11-05 — Phase 6 post-upgrade re-verification

Same method as the pre-upgrade run above (`COMPOSE_PROJECT_NAME=photo-gallery-api-p6`,
`APP_PORT=20060`), re-run after every Phase 6 change landed on
`phase-6/dependency-upgrade` (rebased onto `main`@`ca2e141`, which already
includes Renovate PR `#43`, merged by this phase — see "Dependency-bot PR
triage" below). Upgrades applied, in dependency-map order (runtime/base
image → framework/libraries → dev tools):

| Step | Package / image | Before | After | Commit |
|---|---|---|---|---|
| 1 | `symfony/http-foundation`, `http-kernel`, `routing`, `yaml` (transitive, via `laravel/framework`) | v8.1.10 / v8.1.10 / v8.1.10 / v8.1.9 | v8.1.11 (all four) | `fix(deps): bump symfony/http-foundation, http-kernel, routing, yaml to v8.1.11` (pre-existing on this branch, carried through the rebase) |
| 2 | `php:8.5-apache` base image digest | `sha256:4cc63b3d...` | `sha256:304eac8c...` (confirmed live-current via Docker Hub v2 API `tags/8.5-apache` → `digest` field, and via the actual `docker pull`, matching Renovate PR `#46` exactly) | `chore(deps): update php:8.5-apache docker digest to 304eac8` |
| 3 | `guzzlehttp/guzzle` (direct) | `^7.9` / locked 7.16.1 | `^8.0` / locked 8.3.1 (confirmed live-current via Packagist `p2` endpoint) — also pulled `guzzlehttp/promises` 2.6.0→3.1.0, `guzzlehttp/psr7` 2.13.2→3.2.0, `symfony/deprecation-contracts`→v3.8.1, dropped `ralouphie/getallheaders` | `build(deps): bump guzzlehttp/guzzle to v8.3.1` |
| 4 | `larastan/larastan` (dev) | `^3.13` / locked 3.13.2 | `^3.13` / locked 3.14.0 (lockfile-only; confirmed live-current via Packagist) | merged via Renovate PR `#43` (`ca2e141`), inherited by this branch after rebase |

Every other dependency, runtime, and CI action checked live against its
registry on 2026-11-05 (Packagist `p2`, npm registry, Docker Hub v2 API,
GitHub releases API) was already at latest stable — no further action:
`laravel/framework` v13.40.0, `laravel/sanctum` v4.3.3, `laravel/tinker`
v3.0.2, `laravel/pint` v1.32.1, `phpunit/phpunit` 13.4.6, `fakerphp/faker`
v1.24.1, `laravel/sail` v1.69.0, `mockery/mockery` 1.6.15,
`nunomaduro/collision` v8.9.5, `spatie/laravel-ignition` 2.12.1,
`mysql:26.10` (digest-identical to `mysql:latest`), `composer:2`
(digest-identical to the pin), `actions/checkout` (pinned SHA
`3d3c42e5...` confirmed identical to the `v7.0.1` tag's commit). The
unused npm/Vite scaffolding (`axios`, `laravel-vite-plugin`, `lodash`,
`postcss`, `vite`) is also already at latest per a disposable
`npm outdated`/`npm audit` run; untouched here since it's a Phase 7
dead-code candidate, not a staleness gap (see `deps/photo-gallery-api.md`).

| Gate (CI step) | Command | Result | Exit |
|---|---|---|---|
| Config validate | `docker compose --env-file var/.env config --quiet` | OK | 0 |
| Build test image | `docker compose --env-file var/.env build test` | Built on the new `php:8.5-apache@sha256:304eac8c...` base | 0 |
| Run test suite | `docker compose --env-file var/.env --profile test run --rm test` | PHPUnit 13.4.6, PHP 8.5.12 — **33/33 tests, 77 assertions, 1 deprecation** (same pre-existing `PDO::MYSQL_ATTR_SSL_CA` deprecation, `docs/TECHNICAL_DEBT.md` #10 — unchanged) | 0 |
| Lint (Pint) | `... --entrypoint ./vendor/bin/pint test --test` | PASS, 70 files | 0 |
| **Audit** | `... --entrypoint composer test audit --no-interaction --abandoned=report` | **"No security vulnerability advisories found."** — the baseline's one failure is now fixed | **0** |
| Static analysis (Larastan) | `... --entrypoint ./vendor/bin/phpstan test analyse --memory-limit=512M` | OK, 0 errors | 0 |
| Build app image | `docker compose --env-file var/.env build app` | Built | 0 |
| Build db image | `docker compose --env-file var/.env build mysql` | Built | 0 |

**Conclusion: every gate is green, including the one gate (`composer
audit`) that was red in the pre-upgrade baseline. No regression against
the Phase 5 baseline** — tests, lint, static analysis, and both image
builds stayed green throughout, and were re-verified fresh after the
`origin/main` rebase that absorbed PR `#43`.

### Dependency-bot PR triage (Phase 6)

- **`#46`** (Renovate, `php:8.5-apache` digest bump) — closed as superseded; step 2 above is this phase's own verified-and-committed equivalent.
- **`#43`** (Renovate, larastan v3.14.0) — clean, mergeable, CI green, matched live-latest exactly; merged directly (merge commit `ca2e141`), branch deleted.
- **`#28`** (Renovate, guzzle v7→v8) — closed as superseded; its branch had a stale, conflicting merge base (predates the account-wide AI-attribution history rewrite), so step 3 above redid the identical bump fresh on top of current `main` instead of rebasing the bot's branch.
- **`#45`** (Phase 4 `git-steward`, gitleaks pre-commit hook, `phase-4/git-hygiene`) — **not merged by this phase (R1: one writer per repo, different phase owns this PR)**. Its CI was failing solely because of the `composer audit` finding this phase's step 1 fixes. Once this phase's own PR lands on `main`, PR `#45` needs a rebase/merge-from-main (no code conflict expected — it only touches `.pre-commit-config.yaml`/`.git/hooks` tooling, not `composer.*`) and its CI should then go green; reported to the Orchestrator for a separate merge decision, not done here.
