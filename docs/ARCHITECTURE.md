# Architecture

This complements the "What it does" / "The stack" sections of the root `README.md` with the request-flow and layering detail those sections don't cover. Written as part of the `RasimAghayev`-repos audit (see `projects/info/photo-gallery-api.md` in the outer workspace), 2026-11-03.

## Layering

```
routes/api.php
  -> ImageController (app/Http/Controllers)
       -> StoreImageRequest / UpdateImageRequest (app/Http/Requests) — validation
       -> Image (app/Models) — Eloquent model, casts `type` to int
       -> ImageResource / ImageDetailResource / ImageCollection (app/Http/Resources) — response shaping
       -> Storage::disk('private') — file persistence, outside the public webroot
```

There is no service/repository layer, and none is needed: the controller is four thin CRUD-ish actions plus a signed-file-download action, each a handful of lines. Introducing a service layer here would be the over-engineering CLAUDE.md's working procedure warns against — a pattern earns its place only if it solves a real, present problem, and at this size and stability it doesn't.

## Request flow: write (`POST /api/v1/image`)

1. `auth:sanctum` middleware checks the bearer token (see Auth below). No token -> 401 before the controller ever runs.
2. `StoreImageRequest::rules()` validates `imageName` (unique, max 50), `imageDescription` (max 250), `imageType` (enum 1/2/3), `imageFile` (image, jpeg/jpg/png/gif, max 5MB). A failure returns Laravel's standard 422 with per-field messages (overridden via `messages()` for clearer wording).
3. `ImageController::store()` moves the uploaded file to the `private` disk (`storage/app/private`, outside `public/`) via `$request->file('imageFile')->store('images', 'private')`, then creates the `Image` row with the returned path in `file`.
4. Response: `ImageResource` — deliberately exposes only `name`, `description`, `type` (the brief's requirement; the file is never returned directly here).

## Request flow: read-one (`GET /api/v1/image/{id}`)

No auth required (reading is intentionally open — see README). Returns `ImageDetailResource`, which (unlike the list/create resource) includes a `file` field containing a **signed URL** to `GET /api/v1/image/{id}/file`, generated via Laravel's `URL::temporarySignedRoute` mechanism bound to the `signed` middleware on that route. The signature embeds an expiry (5 minutes, per the route's `signed` middleware default); the server never hands out a permanent, guessable path to a private file.

## Auth model

- **Sanctum, personal access tokens only** (no SPA/cookie session flow is wired up — there's no `EnsureFrontendRequestsAreStateful` usage). A token is a bearer string presented once, every request, like a classic API key.
- Tokens are minted out-of-band via `php artisan api:token <email>` (`app/Console/Commands/IssueApiToken.php`), not through a public registration/login endpoint. This is appropriate for the brief's scope (an admin-operated gallery, not a multi-tenant SaaS) but means **there is no self-service account creation or token-rotation path** — a limitation worth knowing if this ever needs to grow beyond a single-operator tool.
- `ImagePolicy` exists (`php artisan make:policy` scaffolding) but is never registered in `AuthServiceProvider` and every method is an empty stub — it has zero effect on authorization today. All write-authorization is the `auth:sanctum` middleware alone; there is no per-user ownership concept on `Image` (no `user_id` column), so any valid token holder can modify or delete any record. That matches the brief (a single shared gallery, not a multi-tenant one) — not a bug, just worth being explicit about if the scope ever changes.

## Data model

Single table, `images` (migration `2022_11_07_111254_create_galleries_table.php` — note the filename says "galleries", the table/model say "image[s]"; a naming mismatch worth knowing about if you're grepping for migrations by name):
- `name` (string, unique), `description` (string), `type` (tinyint/int, enum-like 1/2/3 with no named meaning), `file` (string, storage path), timestamps.
- No soft deletes — `DeleteOldImages` and `ImageController::destroy()` both hard-delete the row and its file together, which is correct for this domain (an orphaned file with no row would just be wasted disk space, so the delete order — row delete gated by `$path`, then the file — is intentional, not a race-condition risk given SQLite/MySQL's single-writer-per-request model here).

## Why no service layer, no repository pattern, no DTOs

Named explicitly because their absence could otherwise be mistaken for an oversight: at one model, one controller, and four actions, introducing these layers would add indirection without adding testability, flexibility, or clarity — the kind of premature abstraction CLAUDE.md's working procedure explicitly warns against. If this gallery grows a second resource type or gains per-user ownership, that's the point to revisit this decision, not before.
