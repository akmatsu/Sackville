# MSB Budget Site

Internal, authenticated Laravel application for the Matanuska-Susitna Borough's IT budget planning cycle. See [AGENTS.md](AGENTS.md) for domain and architecture background.

## Requirements

- PHP 8.5
- Composer
- Node.js + npm
- SQLite (default local driver; see `.env`)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Or run the bundled setup script, which does all of the above (build included):

```bash
composer run setup
```

## Running the dev environment

```bash
composer run dev
```

This runs `php artisan dev`, which starts, in parallel:

- `php artisan serve` — the app server
- `php artisan queue:listen` — the queue worker
- `php artisan pail` — live log tailing
- `npm run dev` — Vite, for frontend asset changes to show up without a manual build

If a frontend change isn't showing up and this isn't running, that's why — start it, or run `npm run build` for a one-off build.

### Running the scheduler (cron jobs)

`composer run dev` / `php artisan dev` does **not** run the task scheduler. Scheduled jobs — currently the TDX hardware model sync (`routes/console.php`) — need the scheduler running separately.

For local development, run this in its own terminal alongside `composer run dev`:

```bash
php artisan schedule:work
```

This ticks every minute and fires any due scheduled tasks, same as a real cron entry would. Leave it running for as long as you want scheduled jobs (like the TDX sync) to actually fire locally.

In production, don't use `schedule:work`. Instead point a single system cron entry at the scheduler:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

#### Configuring the TDX sync frequency

The TDX hardware model sync's frequency (`app/Jobs/SyncTdxHardwareModels.php`) is stored in the database (`sync_schedules` table) and is read fresh every time the scheduler ticks, so changes take effect without a deploy or restart. Configure it from the "Configure sync schedule" button on the Sync Runs page in `/admin` — daily at a set time, or every N hours (useful to ramp up during budget season).

`config/tdx.php` (`TDX_HARDWARE_SYNC_FREQUENCY` / `TDX_HARDWARE_SYNC_TIME` / `TDX_HARDWARE_SYNC_INTERVAL_HOURS` / `TDX_HARDWARE_SYNC_TIMEZONE` in `.env`) only seeds the initial row on first migration and is a fallback if that table is ever unavailable — it is not read after that. Defaults to 11:00 PM America/Anchorage daily.

To trigger the hardware model sync immediately instead of waiting on the schedule:

```bash
php artisan tdx:sync-hardware-models         # queues it
php artisan tdx:sync-hardware-models --now   # runs it inline, no queue
```

or use the "Sync hardware models now" button on the same Sync Runs page.

## Testing

```bash
php artisan test --compact
```

Scope to a file or filter when iterating:

```bash
php artisan test --compact --filter=SyncTdxHardwareModels
```

## Code style & static analysis

```bash
vendor/bin/pint --dirty --format agent   # fix formatting on changed files
vendor/bin/phpstan analyse                # static analysis (Larastan)
```

Run everything CI runs (config clear, lint check, static analysis, full test suite):

```bash
composer run test
```

## Admin panel

Filament admin panel is available at `/admin`. Auth currently runs through Laravel Fortify; Entra AD login is planned (see [AGENTS.md](AGENTS.md)) but not yet wired up — don't assume it's live.
