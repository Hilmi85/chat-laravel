# TempChat — Agent Notes

## Read first
- `.agents/prd.md` is the product spec (Indonesian); `.agents/design.md` defines UI/UX tokens and rules; `.agents/task-instruction.md` defines the required workflow.
- The chat app is not built yet: only `/` (`resources/views/welcome.blade.php`) and the default Laravel auth scaffold exist. `README.md` is generic Laravel boilerplate.
- After each task or task group, update `.agents/tasklist.md` (create it if missing): mark `[x] ✅`, list changed files, and update overall progress. This is mandatory per `task-instruction.md`.
- `CLAUDE.md` is a stale duplicate of the Laravel Boost installer bootstrap; Boost is not installed in this project.

## Stack and environment
- Laravel 13.44, PHP 8.3 (`composer.json` requires `^8.3`; the PRD mentions 8.4), PHPUnit 12, Tailwind 4, Vite 8, vanilla JS. No Livewire/Inertia/React/Vue; axios is not installed.
- Local `.env` targets MySQL database `chat-laravel` at `127.0.0.1` (user `root`, empty password). `.env.example` defaults to SQLite; tests use in-memory SQLite (`phpunit.xml`). Keep migrations portable across MySQL and SQLite.
- Session, cache, and queue all use the `database` driver, so migrations are required. There is no login; identity is session-based.

## Commands
- Full dev stack: `composer dev` → `php artisan dev` (serve + queue listener + Vite).
- Frontend only: `npm run dev`; production assets: `npm run build`.
- Tests: `php artisan test`; single test: `php artisan test --filter=Name` or `php artisan test tests/Feature/File.php`.
- `composer test` is broken with the installed Composer 2.7.6: it passes the literal `@no_additional_args` to `config:clear`. Use `php artisan test`, or upgrade Composer.
- Format: `vendor/bin/pint`; verify only: `vendor/bin/pint --test`.
- Setup: `composer install`, `npm install`, create the MySQL database `chat-laravel`, `php artisan migrate`, `npm run build`. `composer setup` works only after the database exists.
- Scheduler (once the cleanup command exists): `php artisan schedule:work` locally; production cron runs `php artisan schedule:run`.

## Non-obvious implementation constraints
- There is no `routes/api.php`. Put AJAX endpoints in `routes/web.php` so session identity and CSRF protection work.
- Typing: POST `/room/{code}/typing` only after a true 300 ms debounce; GET `/room/{code}/typing/status` must be read-only (never update `typing_at`). Online means `last_seen` < 30 s; typing means `typing_at` >= now - 3 s.
- Rooms expire 24 h after creation; delete rooms/participants/messages via FK cascade from a scheduled command.
- Limits: name ≤ 30 chars, message ≤ 500; sanitize/escape input, send CSRF with every AJAX call, rate limit 1 message/sec/user.
- Room codes are 8 uppercase alphanumeric characters, collision-resistant; the public URL is `/room/{code}`.
- No HTMLPurifier/Laravel Purifier package is installed. Add one if following the PRD literally, or use `strip_tags` plus escaped output.

## Layout, styling, and traps
- `users` in the PRD means chat participants, but Laravel already ships an auth `users` table/model/factory. Do not add `room_id` to the auth user blindly; use a separate `participants`/`room_users` table (or replace the auth scaffold) and update the seeder.
- Routes live in `routes/web.php`; console/scheduler tasks in `routes/console.php`; middleware registration in `bootstrap/app.php` (there is no `app/Http/Kernel.php`).
- Tailwind 4 is CSS-first: there is no `tailwind.config.js`; define tokens with `@theme` in `resources/css/app.css`. The scaffold loads Instrument Sans in `vite.config.js`/`app.css`, but `.agents/design.md` requires JetBrains Mono + Inter — update both when applying the design.
- Design: flat dark UI, `#161614` neutral, `#1D1D1B` surface, `#5FB5D6` sole accent (one primary action per screen), no gradients.
- `laravel/pao` is installed; `php artisan test` and Pint emit compact JSON. That output is expected.
- This directory is not a git repository and has no CI; there are only the two example tests.
