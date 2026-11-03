# Testing strategy

Written 2026-11-03 as the Phase 5 deliverable per `PROMPT.md`.

## Approach

PHPUnit 11 feature + unit tests, run against an in-memory SQLite database — no network or external database dependency, matching the repo's own `phpunit.xml` (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`). Every file-backed assertion uses `Storage::fake('private')`, never the real disk.

**Mock only where it makes sense.** The suite uses Laravel's facade mocking (`DB::shouldReceive(...)`) in exactly one place — simulating a database outage for the readiness probe — because that's the one scenario a real in-memory SQLite connection can't naturally produce (SQLite doesn't fail the way a networked MySQL connection does). Everything else runs against the real (in-memory) database and the real validation/auth/resource-transformation code paths; nothing is mocked for convenience.

## Coverage by layer

- **API/feature** (`tests/Feature/ImageApiTest.php`): every one of the 6 endpoints, both the golden path and its failure modes — missing/invalid auth (401 on create/update/delete), validation (name length, description length, type range, file mime, file size, name uniqueness) on both create and update, not-found (404) on show/update/delete, the signed temporary file URL (valid, expired/missing signature, and the actual file download).
- **Scheduled command** (`tests/Feature/DeleteOldImagesTest.php`): the 30-day cutoff and that a removed record's file is removed with it.
- **Resource transformation** (`tests/Unit/ImageResourceTest.php`): the summary shape, that the file path never leaks into a response, the temporary-URL generation, and the type-cast to integer.
- **Health endpoints** (`tests/Feature/HealthTest.php`, added in this Phase 5 pass alongside the Phase 4 endpoints themselves): liveness always reports `ok` without touching the database; readiness reports `ok` when the database answers and `503` when it doesn't (mocked, per above); liveness specifically does not call the database at all (`DB::shouldReceive('connection')->never()`), so a DB outage can never take down the liveness probe.

## What is deliberately not tested

- **Rate limiting (429)** — the `RateLimiter::for('api', ...)` config (60/min) is Laravel framework behavior, not application logic; testing it would mean asserting against a third-party rate limiter's internals rather than this app's code.
- **The dead `ImagePolicy`** — not registered in `AuthServiceProvider`, has no effect on any request, and is already tracked as cleanup debt (`TECHNICAL_DEBT.md` #4). Testing dead code would test nothing real.
- **Multi-page pagination beyond page boundaries** — the existing `test_the_listing_holds_ten_records_at_a_time` already exercises Laravel's own `paginate()`, which is framework-tested; this repo adds no custom pagination logic on top of it.

## Running the suite

```shell
make test       # phpunit — all tests
make lint        # Laravel Pint
make audit       # composer audit
make analyse     # Larastan
```

Each wraps `docker compose --env-file var/.env --profile test run --rm ...` — see the `Makefile`.
