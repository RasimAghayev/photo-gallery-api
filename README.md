# AbyssTask

Laravel 10 API + cron job project built as a developer recruitment task.

## Overview

This repository implements the Abyss recruitment task requirements:

1. **Laravel project** — Laravel 10 boilerplate (note: task spec requested Laravel 9; project uses Laravel 10 per `composer.json`)
2. **Image API** — REST API for saving, listing, and showing image records with pagination
3. **Validation** — Input validation for name (string, max 50), description (string, max 250), file (image, max 5MB), type (enum: 1, 2, 3)
4. **Image storage** — Images saved to private storage folder (not publicly accessible)
5. **Pagination** — Listing endpoint returns 10 records per page
6. **Cron job** — Scheduled command to delete records older than 30 days (hourly)
7. **Seed** — Database seeders included

## Architecture

### Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 10 (`laravel/framework: ^10.0`) |
| API Auth | Sanctum (`laravel/sanctum: ^3.0`) |
| PHP | `^8.0.2` |
| Database | MySQL |
| Testing | PHPUnit 9.5 |
| Code Style | Laravel Pint |

### Project Structure

```
AbyssTask/
├── app/
│   ├── Console/
│   │   └── Kernel.php          # Cron schedule + command registration
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php   # Base controller
│   │   │   └── ImageController.php  # REST API controller (index, store, show, update, destroy)
│   │   ├── Requests/           # Form requests (StoreImageRequest, UpdateImageRequest)
│   │   └── Resources/          # API Resources (ImageCollection, ImageResource)
│   ├── Models/
│   │   ├── Image.php           # Image model (fillable: name, description, type, file)
│   │   └── User.php
│   └── ...
├── database/
│   ├── migrations/
│   │   ├── 2022_11_07_111254_create_galleries_table.php  # images table
│   │   ├── 2014_10_12_000000_create_users_table.php
│   │   ├── 2014_10_12_100000_create_password_resets_table.php
│   │   └── 2019_08_19_000000_create_failed_jobs_table.php
│   ├── seeders/                # Database seeders
│   └── factories/
├── routes/
│   └── api.php                 # API routes (v1/image resource)
├── composer.json
├── package.json
├── vite.config.js
└── artisan
```

### API Endpoints

All endpoints are under `/api/v1/image` (RESTful resource):

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/image` | List all images (paginated by 10) |
| POST | `/api/v1/image` | Save a new image record |
| GET | `/api/v1/image/{id}` | Show a single image record |
| PUT | `/api/v1/image/{id}` | Update an image record |
| DELETE | `/api/v1/image/{id}` | Delete an image record |

### Database Schema

`images` table:
- `id` — bigint (primary key)
- `name` — varchar(50), required
- `description` — varchar(250), required
- `type` — enum('1', '2', '3'), default '1'
- `file` — varchar(250), image path (private storage)
- `created_at` / `updated_at` — timestamps

## Running

### Prerequisites

- PHP 8.0.2+
- Composer
- MySQL
- Node.js + npm (for Vite frontend, if needed)

### Setup

```bash
# Clone
git clone https://github.com/RasimAghayev/AbyssTask.git
cd AbyssTask

# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=your_database
# DB_USERNAME=your_user
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Start dev server
php artisan serve
```

### Testing

```bash
php artisan test
# or
vendor/bin/phpunit
```

### Cron Job (Delete Old Records)

The cron job is configured in `app/Console/Kernel.php` to delete records older than 30 days, scheduled to run hourly:

```php
// In app/Console/Kernel.php → schedule()
$schedule->command('images:cleanup')
    ->hourly()
    ->appendOutputTo(storage_path('logs/cron.log'));
```

Register the cron job on your server:
```bash
* * * * * cd /path/to/AbyssTask && php artisan schedule:run >> /dev/null 2>&1
```

## Task Source

This repository was built as a developer recruitment task for [Abyss](https://github.com/RasimAghayev/AbyssTask). The full task description is in [Task.md](Task.md).

## License

MIT
