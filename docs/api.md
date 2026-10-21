# The API of photo-gallery-api

Every endpoint, with its request, its response and the rules that hold for
it. The base path of the API is `/api/v1`.

## The shape of a response

A successful answer carries the flag `success`, a message and the payload
under the key `data`:

```json
{
  "success": true,
  "message": "Image created successfully.",
  "data": {
    "name": "A photo of a cat",
    "type": 2,
    "description": "A cat that sits on a table."
  }
}
```

A failed answer carries the flag `success` set to `false` and a message:

```json
{
  "success": false,
  "message": "Image not found."
}
```

A request that does not pass the validation is answered with the status 422
and the errors of every field:

```json
{
  "message": "The imageName field is required.",
  "errors": {
    "imageName": ["A name for the image is required."]
  }
}
```

Every answer of the API is JSON, whatever the request asked for.

## The authentication

The reading is open. The writing asks for a token of Sanctum in the header:

```
Authorization: Bearer <token>
```

A token is issued by the command `php artisan api:token <email>`, which
creates the user when it does not exist yet.

A request without a token is answered with the status 401:

```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

## The listing

### `GET /api/v1/image`

**Parameters**

| Parameter | Type | Default | Meaning |
|---|---|---|---|
| `page` | integer | 1 | The page of the listing. |

The brief of the task asks for ten records at a time.

**Response**

```json
{
  "success": true,
  "message": "Images retrieved successfully.",
  "data": [
    {
      "name": "A photo of a cat",
      "type": 2,
      "description": "A cat that sits on a table."
    }
  ],
  "links": {
    "first": "http://localhost:8080/api/v1/image?page=1",
    "last": "http://localhost:8080/api/v1/image?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "from": 1,
    "to": 1,
    "total": 1
  }
}
```

The answer holds the name, the type and the description of a record, and
nothing else. The path of the file is not part of it: the files live on a
private disk, and their path says nothing to a consumer.

## One record

### `GET /api/v1/image/{id}`

The status 200 with the record and a temporary address of its file, or the
status 404 with `"message": "Image not found."`.

```json
{
  "success": true,
  "message": "Image retrieved successfully.",
  "data": {
    "name": "A photo of a cat",
    "type": 2,
    "description": "A cat that sits on a table.",
    "url": "http://localhost:8080/api/v1/image/1/file?expires=1758444000&signature=...",
    "expires_in": 300
  }
}
```

The address carries a signature. It stops working after five minutes, and a
request without a valid signature is answered with the status 403.

### `POST /api/v1/image`

**Body** — the fields carry the names of the brief.

| Field | Type | Rule |
|---|---|---|
| `imageName` | string | Required, between 3 and 50 characters, not already taken. |
| `imageDescription` | string | Required, between 3 and 250 characters. |
| `imageType` | integer | Required, 1, 2 or 3. |
| `imageFile` | file | Required, a picture of jpeg, jpg, png or gif, at most 5 megabytes. |

**Response:** the status 201 with the created record. The answer holds the
name, the type and the description, and nothing else.

### `PUT /api/v1/image/{id}`

The same body as the creation. The name, the description and the type are
required; the file is not, and a record without a new file keeps the file it
has. A new file replaces the old one, and the old file is removed from the
disk.

The status 200 with the changed record, or the status 404.

### `DELETE /api/v1/image/{id}`

The status 200. The row is gone, and the file of the row is gone with it.

## The file of a record

### `GET /api/v1/image/{id}/file`

The address of this endpoint carries a signature, and the middleware
` signed` refuses an address without a valid one with the status 403.

The status 200 with the file itself, or the status 404 when the record has
no file or the file is not on the disk.

## The rate limit

The api group of the framework carries the rate limiter `api`: 60 requests
per minute and per address. A request that passes the limit is answered with
the status 429.

## The scheduled command

```shell
php artisan images:delete-old
```

Removes the records that are older than 30 days and the files that belong to
them, and prints how many of each were removed. The command runs every hour.
