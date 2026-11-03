# Test report

Last run 2026-11-03, inside the project's own Docker test image (`docker compose --env-file var/.env --profile test run --rm test`), against an in-memory SQLite database. See `docs/TESTING_STRATEGY.md` for what each layer covers and why.

## Result

```
PHPUnit 11.5.57, PHP 8.4.27
33 / 33 (100%)
OK (33 tests, 77 assertions)
```

0 failures, 0 errors, 0 skipped.

## By class

| Class | Tests | What it covers |
|---|---|---|
| `Tests\Feature\DeleteOldImagesTest` | 2 | The 30-day scheduled cleanup, and that it removes the file with the row. |
| `Tests\Feature\HealthTest` | 4 | Liveness/readiness golden path, readiness under a simulated DB outage (mocked), and that liveness never depends on the database. |
| `Tests\Feature\ImageApiTest` | 23 | All 6 endpoints: listing/shape, create (auth, validation ×5, uniqueness), read-one + signed file URL (valid/expired/missing signature/404), update (auth, validation, 404, file replacement), delete (auth, 404). |
| `Tests\Unit\ImageResourceTest` | 5 | Response-shape correctness and the private file path never leaking into a response. |

**24 pre-existing + 9 added this Phase 5 pass** (4 for the Phase 4 health endpoints, 5 closing auth/validation/not-found gaps on the update and delete endpoints that the pre-existing suite didn't exercise).

## Other quality gates, same run

| Gate | Result |
|---|---|
| Laravel Pint | ✅ 70 files, 0 violations |
| `composer audit` | ✅ 0 vulnerabilities |
| Larastan (level 5) | ✅ 0 errors (9 pre-existing findings baselined, see `phpstan-baseline.neon`) |

## Known limitations

- Rate limiting (429) is framework behavior (`RateLimiter::for('api', ...)`), not application logic — not covered here; see `docs/TESTING_STRATEGY.md`.
- Coverage percentage is not measured — the project has no Xdebug/PCOV build step in its Docker test image, and adding one solely to produce a number wasn't judged worth the added image complexity for a repo this size. Every endpoint and every validation rule in `docs/api.md`/`openapi.yaml` has at least one corresponding test, verified by the table above, not by a coverage tool.
