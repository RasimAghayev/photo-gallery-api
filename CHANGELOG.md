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
