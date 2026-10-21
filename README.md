# photo-gallery-api

An image gallery API with a scheduled cleanup, built as a developer
recruitment task. The brief is in [`Task.md`](Task.md).

Laravel 12 on PHP 8.4, MySQL 8.4, and a Docker Compose stack for the
development, the tests and the production build.

## What it does

- **Records** hold a name (at most 50 characters), a description (at most 250
  characters), a type (1, 2 or 3), a picture and the dates of the record.
- **The pictures live on a private disk.** They have no address of their own.
  A consumer that asks for one record gets a temporary address that carries a
  signature and stops working after five minutes.
- **The listing returns ten records at a time**, and only the name, the type
  and the description of a record.
- **The reading is open**, because a gallery is meant to be read, and **the
  writing asks for a token** of Sanctum.
- **A scheduled command removes the records that are older than 30 days**,
  together with the files that belong to them.

The contract of every endpoint is in [`docs/api.md`](docs/api.md).

## Running it

```shell
mkdir -p var
cp .env.example var/.env
docker compose --env-file var/.env build
docker compose --env-file var/.env up -d
```

Then:

```shell
docker compose --env-file var/.env exec app php artisan migrate
docker compose --env-file var/.env exec app php artisan api:token tester@example.com
```

The command prints a token. Send it in the header
`Authorization: Bearer <token>`.

Every command of the stack needs `--env-file var/.env`, because the variables
of the stack live in that file and not in the root of the project. The
passwords of the database have no default value: the stack refuses to start
until they are set.

The application is reachable at `http://localhost:8080`. `APP_PORT` in
`var/.env` moves it to another host port.

Every bind mount of the stack lives under `var/`: the local environment file,
the data of the database and the files of the gallery. Named volumes are not
used.

The key of the application is read from the environment. When the environment
does not carry one, the container generates a random key for itself and writes
a note about it into the log.

## The endpoints

| Method | Path | Auth | What it does |
|---|---|---|---|
| GET | `/api/v1/image` | – | The records, ten at a time. |
| GET | `/api/v1/image/{id}` | – | One record, with a temporary address of its file. |
| POST | `/api/v1/image` | token | Creates a record. |
| PUT | `/api/v1/image/{id}` | token | Changes a record. |
| DELETE | `/api/v1/image/{id}` | token | Removes a record and its file. |
| GET | `/api/v1/image/{id}/file` | signature | The file itself, through a signed address. |

The fields of a request carry the names of the brief: `imageName`,
`imageDescription`, `imageType` and `imageFile`.

## Tests

Both gates run inside the project's own Docker images. Nothing has to be
installed on the machine.

```shell
docker compose --env-file var/.env --profile test run --rm test                                  # phpunit
docker compose --env-file var/.env --profile test run --rm --entrypoint ./vendor/bin/pint test --test   # the linter
docker compose --env-file var/.env --profile test run --rm --entrypoint composer test audit --no-interaction --abandoned=report
```

The suite runs against an in memory SQLite database, so it needs neither a
database nor a network. It covers the listing, the two writes, the validation
of the request, the authentication, the temporary address of the file and the
scheduled command.

The linter is Laravel Pint. The rules are the ones of Laravel, and the
directories of the runtime state are excluded in `pint.json`.

## CI/CD

`.github/workflows/ci.yml` runs the same gates on every push and every pull
request:

| Step | Command | Gate |
|---|---|---|
| Validate the compose file | `docker compose --env-file var/.env config --quiet` | ✅ |
| Build the test image | `docker compose --env-file var/.env build test` | ✅ |
| Test suite | `docker compose --env-file var/.env --profile test run --rm test` | ✅ |
| Linter | `pint --test` | ✅ |
| Dependency audit | `composer audit` | ✅ |
| Build the application image | `docker compose --env-file var/.env build app` | ✅ |
| Build the database image | `docker compose --env-file var/.env build mysql` | ✅ |

The runner needs a Docker daemon and nothing else — no PHP toolchain is
installed on it. The workflow reads the repository only, never prints a
secret and never publishes anything, and `actions/checkout` is pinned to a
commit hash so the tag cannot move under the pipeline.

Two permanent branches exist: `master` (the released state) and `test` (the
integration state). A change is delivered as a pull request from a
`feature/*` branch into `test`, and from `test` into `master`.

## The stack

| Part | Choice |
|---|---|
| Language | PHP 8.2 or newer, the image runs 8.4 |
| Framework | Laravel 12 |
| Database | MySQL 8.4, SQLite for the tests |
| Authentication | Sanctum, a token per caller |
| Pictures | a private disk of the application, a signed address to read one |
| Web server | Apache with the rewrite module, the workers run as `www-data` on port 8080 |
| Tests | PHPUnit 11, Laravel Pint as the linter |
| Dependency audit | `composer audit` |

The base images are pinned by digest, so a rebuild of the project uses the
same runtime as the last one.

## The scheduled command

```shell
docker compose --env-file var/.env exec app php artisan images:delete-old
```

The command removes the records that are older than 30 days and the files
that belong to them. It runs every hour, and the schedule is declared in
[`routes/console.php`](routes/console.php). The runner of the schedule has to
be started on the server:

```shell
* * * * * cd /path/to/photo-gallery-api && php artisan schedule:run >> /dev/null 2>&1
```

## Known limitations

- **The gallery has no search.** The listing returns the records in the order
  of their id, and the only parameter is the size of the page.
- **A record is not versioned.** A change overwrites the old values.
- **A record is removed after 30 days.** There is no way to keep one for
  longer.
- **The pictures are not processed.** The file is stored as it was uploaded.
- **The type of a record is a number of the enumeration 1, 2 and 3**, and
  nothing says what the three types mean.
- **The address of a file is valid for five minutes**, and there is no way to
  extend it.
- **No license file.**
