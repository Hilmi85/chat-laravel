# TempChat

A temporary, anonymous chat room built with Laravel 13, MySQL, Tailwind CSS 4, and vanilla JavaScript.

Rooms are created without an account, shared by an 8-character code, and automatically deleted after 24 hours. Messages update through AJAX polling every 2 seconds, with a read-only typing indicator, online count, countdown, sound notifications, and join/leave system messages.

## Features

- Create a room with a name and a unique 8-character code.
- Join through `/room/{code}`; duplicate names get a `(2)`, `(3)`, … suffix.
- Realtime-ish message polling every 2 seconds.
- Typing indicator with a true 300 ms debounce.
- Online count based on `last_seen` within 30 seconds.
- Room countdown and expired-room handling.
- System messages for join and leave.
- Copy link, WhatsApp/Telegram sharing, sound toggle, and toast notifications.
- Input sanitization with `mews/purifier`, CSRF protection, and per-participant message rate limiting.
- Scheduled cleanup deletes expired rooms and cascades participants/messages.

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+ and npm
- MySQL

## Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create the MySQL database named `chat-laravel` (the default in `.env.example`), then run:

```bash
php artisan migrate
npm run build
```

If you use different database credentials, update `DB_*` in `.env` before migrating.

## Run locally

Start the Laravel server, queue listener, and Vite together:

```bash
composer dev
```

Or run them separately:

```bash
php artisan serve
npm run dev
```

Open `http://localhost:8000`.

## Scheduler

Room cleanup is registered in `routes/console.php` and runs every minute. For local development:

```bash
php artisan schedule:work
```

In production, add the usual Laravel scheduler cron entry:

```cron
* * * * * cd /path/to/tempchat && php artisan schedule:run >> /dev/null 2>&1
```

Run cleanup manually:

```bash
php artisan rooms:cleanup
```

## Tests and formatting

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Use `php artisan test` rather than `composer test`; the Composer test script is currently incompatible with the installed Composer 2.7.6.

## Project notes

- `rooms`, `participants`, and `messages` are the chat tables. Laravel's default auth `users` table is intentionally left untouched and unused.
- All AJAX endpoints live in `routes/web.php` so session identity and CSRF protection work.
- Expired-room checks are handled by `App\Http\Middleware\EnsureRoomIsActive`.
- Product requirements and design tokens are documented in `.agents/prd.md` and `.agents/design.md`.
- The current UI uses the single cyan accent from the design system (`#5FB5D6`), with a small semantic warning color for the under-one-hour countdown state.
