# Repository audit

Audited 2026-11-03 as repo #4 of `REPOS_INDEX.md`. Backup: `projects/info/backups/photo-gallery-api.git`. Working clone: `projects/photo-gallery-api/`.

This repo started life as a developer-recruitment coding test (`Task.md`) and has since been modernized by the repo owner well past the brief. **It is complete, working, and already follows strong practice** — this audit is deliberately lighter than `be-chatty`'s (repo #3), because most of what that audit had to fix (CI, Docker, env hygiene, dead config) is already done well here. Every finding below was verified by actually re-running the repo's own CI gates locally via Docker, not just by reading code.

## Git history

Git history is clean and incremental: feature branches merged via PR, a visible progression from a Laravel 10 skeleton to the current Laravel 12 container-and-tests setup (`d7d5529 chore: drop the skeleton of laravel 10 for good`), dependency-bot commits interleaved normally. No force-pushes, no broken merges observed in `git log --oneline -20` or the mirror backup.

Full-history secrets scan (`git log --all -p`, same method used for `be-chatty`): regex-matched for `password`/`secret`/`api_key`/`token` assignments with non-placeholder-looking values, filtered out known placeholders (`your_`, `example`, `null`, `${`, `abyss`, `root`, etc.). **Zero matches.** No real credential has ever been committed.

## Branches

Default branch is `master` (not `main` — flagged under CLAUDE.md Approval Gate #4, same as `be-chatty`). A `test` branch exists, matching the `main`/`test` convention CLAUDE.md recommends — but `git log origin/test..origin/master` shows `master` is currently 12 commits ahead of `test` with `test` contributing nothing back, meaning in practice bot PRs (Renovate/Dependabot) merge straight into `master`, not through `test` as the README's own documented flow (`feature/* -> test -> master`) describes. This is a process-drift observation, not a defect — low risk for a solo-maintained repo, but worth knowing if branch protection is ever added.

9 open bot PRs (all Renovate), 0 open issues, no stale/abandoned feature branches beyond the bot branches matching the open PRs.

## CI/CD

`.github/workflows/ci.yml` — a genuinely well-built pipeline: Docker-only (the runner has no PHP toolchain installed; every gate runs inside the project's own images), `actions/checkout` pinned to a commit SHA (not a floating tag), `permissions: contents: read` (the workflow never writes anything). Triggers on push/PR to `master` and `test`.

**Every gate was independently re-run locally during this audit** (not just read from the YAML) to confirm the pipeline is actually green today, not just well-written:

| Gate | Command | Result |
|---|---|---|
| Compose config validation | `docker compose --env-file var/.env config --quiet` | ✅ passes |
| Test image build | `docker compose --env-file var/.env build test` | ✅ builds clean |
| Test suite | `docker compose --env-file var/.env --profile test run --rm test` | ✅ **24/24 tests pass, 60 assertions** |
| Linter | `pint --test` | ✅ **68 files, 0 violations** |
| Dependency audit | `composer audit --abandoned=report` | ⚠️ **1 medium-severity finding** (see Dependencies below) |
| App image build | `docker compose --env-file var/.env build app` | ✅ builds clean |
| DB image build | `docker compose --env-file var/.env build mysql` | ✅ builds clean |

## Docker / containerization

Both the `app` and `mysql` Dockerfiles are already following best practice: multi-stage builds, base images pinned by digest (not just tag — a `mysql:8.4@sha256:...` style pin), a non-root runtime user (`www-data`) for the application process, a `HEALTHCHECK` on the app image, a dedicated `test` build stage that never ships to the runtime image, resource limits (`cpus`/`memory`/`pids`) declared on every compose service, and required (non-defaulted) database credentials enforced via Compose's `${VAR:?error message}` syntax — a stronger posture than `be-chatty`'s original hardcoded-default pattern (now fixed there too). No gaps found worth a P0/P1 finding.

## Tests

24 PHPUnit tests across `tests/Feature/ImageApiTest.php`, `tests/Feature/DeleteOldImagesTest.php`, `tests/Unit/ImageResourceTest.php` — covering the listing, both writes, request validation, authentication, the signed temporary file URL, and the scheduled cleanup command. All 24 pass against an in-memory SQLite database (no network or external DB dependency needed for the suite, unlike `be-chatty`'s `mongodb-memory-server` situation). No test-infrastructure issues of the kind found in `be-chatty` (no Node-version equivalent problem exists for a Docker-isolated PHP test run).

## Configuration / secrets handling

`.env.example` contains only placeholder values; `var/.env` (the real local file) is gitignored. Docker Compose requires every database credential explicitly (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` all use `${VAR:?...}` — the stack refuses to start without them, rather than silently falling back to a known default). **Conclusion: no real secret has ever been committed, and the current hygiene is already better than what `be-chatty` needed fixing.**

## Dead/placeholder code

- `ImagePolicy` (`app/Policies/ImagePolicy.php`) — every method is an empty stub, and it's never registered in `AuthServiceProvider::$policies` (which is itself an empty array with only a commented-out example line). Dead `artisan make:policy` scaffolding with zero effect on authorization — all write-access control is the `auth:sanctum` middleware alone. Not a vulnerability (nothing relies on it to deny anything), just unused code.
- Default Laravel frontend scaffolding — `resources/js/`, `resources/css/`, `resources/views/welcome.blade.php`, `vite.config.js`, `package.json` — present but unused. This is a pure JSON API (enforced via `ForceJsonResponse` middleware); `routes/web.php` only serves the stock `welcome` view, and nothing in the app links to it.
- Migration file is literally misnamed: `database/migrations/2022_11_07_111254_create_galleries_table.php` creates the `images` table (verified by reading the file) — a holdover from an earlier naming idea ("gallery") that never got renamed when the table became `images`. Cosmetic only; migrations are identified by timestamp, not filename, so this has zero functional effect.

## Dependencies

- `composer audit --abandoned=report` (run locally, matching CI exactly): **1 medium-severity finding** — `symfony/mime` v7.4.19 (a transitive dependency pulled in by `laravel/framework`, not directly required) is vulnerable to **CVE-2026-45070** (email header injection via non-token characters in MIME parameter names), fixed in 7.4.22+. The existing `composer.json` doesn't pin `symfony/mime` directly, so this should resolve via a normal `composer update symfony/mime` within already-declared constraints — no major-version bump needed.
- **9 open Renovate PRs**, split by actual risk (checked each PR's touched files, not just its title, the same method used to catch `be-chatty`'s `@types/*` mismatches):
  - **4 safe, lockfile/digest-only, no `composer.json`/major-tag change:** `#40` (guzzle 7.16.1, in-range patch), `#39` (`mysql:8.4` digest bump), `#38` (`composer:2` digest bump), `#34` (`php:8.4-apache` digest bump).
  - **5 real version-range or major-tag changes, each touching `composer.json` or a Dockerfile's primary version tag — flagged for separate review, not blindly merged in this pass:** `#37` (`laravel/framework` v12→v13, a framework major), `#28` (`guzzlehttp/guzzle` v7→v8, a major), `#32` (PHP Docker tag 8.4→8.5, a new PHP minor release needing a compatibility check), `#20` (PHPUnit v11→v13, dev-only but still a 2-major jump), `#41` (MySQL Docker tag 8.4→26.10 — Oracle switched MySQL's own Docker tagging scheme to a calendar-based one; this is a real database engine upgrade, not a cosmetic tag bump, and needs its own evaluation of migration/compatibility risk before merging).
- 0 open human-authored issues.
