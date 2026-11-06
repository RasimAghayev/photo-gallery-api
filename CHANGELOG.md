# Changelog

All notable changes to this project are documented here. Format loosely
follows [Keep a Changelog](https://keepachangelog.com/); this file starts
with Phase 6 of the multi-repo maintenance program (no earlier
project-wide changelog existed).

## Phase 6 — Dependency / runtime / CI-action upgrade to latest stable (2026-11-05)

### Security

- Fixed 7 security advisories across 4 transitive Symfony packages
  pulled in by `laravel/framework` (`symfony/http-foundation`,
  `symfony/http-kernel`, `symfony/routing` — including
  **CVE-2026-118516**, host placeholders in compiled URL matchers could
  match the start of a path and bypass path-based access control — and
  `symfony/yaml`), all disclosed 2026-11-04. Bumped to v8.1.11
  (lockfile-only; no `composer.json` constraint change). `composer
  audit` now reports zero advisories (was failing with 7).

### Changed

- `build/php/Dockerfile`: `php:8.5-apache` base image digest updated to
  `sha256:304eac8c...` (the current manifest-list digest for the
  `8.5-apache` tag; confirmed live via the Docker Hub v2 API).
- `guzzlehttp/guzzle` direct dependency raised from `^7.9` (locked
  7.16.1) to `^8.0` (locked 8.3.1, the current latest stable per
  Packagist). Transitively updated `guzzlehttp/promises` to 3.1.0,
  `guzzlehttp/psr7` to 3.2.0, and `symfony/deprecation-contracts` to
  v3.8.1; dropped the now-unneeded `ralouphie/getallheaders`.
- `larastan/larastan` (dev) lockfile-refreshed from 3.13.2 to 3.14.0
  within its existing `^3.13` constraint, via Renovate PR #43.

### Dependency-bot PR triage

- Merged **#43** (larastan v3.14.0 lockfile bump) — clean, green, and
  equal to the live-latest version.
- Closed **#46** (php:8.5-apache digest bump) as superseded — this
  phase's own `build/php/Dockerfile` commit applies the identical
  digest, verified in Docker rather than taken on faith from the bot.
- Closed **#28** (guzzle v7→v8) as superseded — its branch had a stale,
  conflicting merge base from before this account's AI-attribution
  history rewrite; the same bump was redone fresh on top of current
  `main` instead.
- Added `renovate.json` — no Renovate/Dependabot config previously
  existed for this repo at any level, so the bot ran on pure defaults
  (unscheduled, ungrouped, unlimited PRs). The new config schedules
  weekly, caps concurrent/hourly PR volume, and groups non-major
  dev-tooling and Docker base-image bumps while keeping every major
  bump individual and non-automerged.

### Verified

Every gate in `.github/workflows/ci.yml` / `Makefile` re-run green in
Docker after each step (see `docs/BASELINE.md` for the full pre- and
post-upgrade evidence table): config validate, test image build,
PHPUnit (33/33, 77 assertions, 1 pre-existing deprecation, unchanged),
Pint lint (70 files), `composer audit` (now clean), Larastan analyse (0
errors), app image build, db image build. No regression against the
Phase 5 baseline.

Every other dependency, runtime, and CI action (laravel/framework,
laravel/sanctum, laravel/tinker, laravel/pint, phpunit/phpunit,
fakerphp/faker, laravel/sail, mockery/mockery, nunomaduro/collision,
spatie/laravel-ignition, `mysql:26.10`, `composer:2`,
`actions/checkout`) was independently checked live against its
registry on 2026-11-05 and was already at latest stable — no action
needed.

## Phase 7 — Dead code & restructure (2026-11-07)

### Fixed

- `config/database.php`: PHP 8.5's `PDO::MYSQL_ATTR_SSL_CA` deprecation
  notice was being echoed to raw output before Laravel sent response
  headers, cascading into a "headers already sent" failure on every
  subsequent `header()` call — including the framework's own
  exception-handler attempt to render a clean error page. The net
  effect was a broken HTTP response on **every route** of the live
  app, undetected by the test suite because PHPUnit's CLI runner
  doesn't exercise the same header-send path a real HTTP response
  does. Already tracked as `docs/TECHNICAL_DEBT.md` #10 but understated
  as a cosmetic P3; fixed by guarding with
  `class_exists(\Pdo\Mysql::class)` to use the non-deprecated PHP
  8.4+ constant while keeping `php: ^8.2` compatibility. Verified via
  a live smoke test of every route, before and after.

### Removed

- `app/Policies/ImagePolicy.php` — every method was an empty stub,
  never registered in `AuthServiceProvider::$policies`. Its 7 matching
  `phpstan-baseline.neon` entries were removed with it.
  `docs/TECHNICAL_DEBT.md` #4.
- `guzzlehttp/guzzle` direct dependency — zero application-code usage
  (grep across `app/`, `routes/`, `config/`, `tests/`); stays installed
  transitively since `laravel/framework` requires it for its own HTTP
  client.
- `laravel/sail` dev dependency — zero usage anywhere; this repo's
  actual local-dev workflow is the custom Dockerfile/docker-compose
  stack, not Sail.
- `resources/js/`, `resources/css/`, `vite.config.js`, `package.json`
  — the stock Laravel frontend build toolchain, unused in this JSON
  API (no `@vite()` directive anywhere, no npm/node step in the
  Dockerfile or CI). Trimmed the matching `.gitignore` entries.
  **`resources/views/welcome.blade.php` and its `/` route were kept** —
  verified live that they're genuinely reachable (`RouteServiceProvider`
  registers `routes/web.php` under the `web` group, exempt from the
  `api`-only `ForceJsonResponse` middleware); `docs/TECHNICAL_DEBT.md`
  #5 and `docs/REPOSITORY_AUDIT.md` both incorrectly claimed this view
  was unreachable and were corrected in the same commit.

### Changed

- `app/Http/Middleware/VerifyCsrfToken.php` renamed to
  `PreventRequestForgery.php`, extending Laravel 13's current
  `Illuminate\Foundation\Http\Middleware\PreventRequestForgery` instead
  of the deprecated alias; `config/sanctum.php`'s middleware key
  updated from `verify_csrf_token` to `validate_csrf_token` to match.
  `docs/TECHNICAL_DEBT.md` #11.

### Verified

Full gate re-run green after every commit (see `docs/BASELINE.md` for
the complete per-commit evidence): config validate, test image build,
PHPUnit (33/33 tests, 77 assertions — deprecation count dropped from 1
to 0), Pint lint (69 files, down from 70), `composer audit` (clean),
Larastan analyse (0 errors), app image build, db image build, plus a
live smoke test of `GET /`, `GET /health/live`, and the main API
routes before and after the dependency/scaffolding removals. No
regression against the Phase 6 baseline; the deprecation-count drop is
a deliberate, verified improvement.

Tooling used: Larastan/PHPStan level 5 (existing CI gate), Psalm 6
targeted `--find-references-to` mode (its broad
`--find-unused-code=always` mode proved unreliable for this stack —
no visibility into Laravel's dynamic provider/middleware/route-closure
registration), and manual grep for dynamic dispatch and route
wiring. `composer-unused/composer-unused-plugin` was attempted but
failed at runtime on this stack's PHP 8.5/Symfony 8.x combination
(confirmed incompatibility, abandoned).

No Approval Gate was triggered: `app/Policies/ImagePolicy.php` was
checked against the Gate 5 "published library symbol" guardrail from
the `php-query-builder` Phase 7 precedent and found not applicable —
`composer.json` declares `"type": "project"`, not a published library
with external consumers.
