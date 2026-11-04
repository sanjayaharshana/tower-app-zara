# Zara Tower Challenge

Mobile stacking game for Zara: players enter their name and mobile number, stack moving Zara packs for 30 seconds, and submit their score to a shared leaderboard. An admin panel lists players and games and exports them as CSV.

## Run locally

```bash
composer install
cp .env.example .env        # then set ADMIN_PASSWORD
php artisan key:generate
php artisan migrate
php artisan serve
```

- Game: http://localhost:8000
- Admin: http://localhost:8000/admin (password = `ADMIN_PASSWORD` in `.env`)

Tests: `php artisan test`

## Where things are

| What | Where |
| --- | --- |
| Game page | `resources/views/game.blade.php` |
| Game script, styles, pack images | `public/game/` (no build step) |
| Game API (`/game/start`, `/finish`, `/submit`, `/leaderboard`) | `app/Http/Controllers/GameController.php` |
| Admin panel | `app/Http/Controllers/AdminController.php`, `resources/views/admin/` |
| Settings (admin password, leaderboard size, score check, timezone) | `config/game.php` |

Each round is a row in `plays`; each mobile number is one row in `players`, which keeps that player's best submitted score. Mobile numbers are stored as `07XXXXXXXX` (Sri Lankan format; the rule is `Player::PHONE_PATTERN`, mirrored by `PHONE_PATTERN` in `public/game/game.js`). The public leaderboard shows names only, never numbers.

The browser reports the score, so `/game/finish` rejects a score higher than the time actually played allows (`max_points_per_second` in `config/game.php`). That stops casual tampering; no browser game is fully cheat-proof, so check the top entries before awarding prizes.

## Deploy

1. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `ADMIN_PASSWORD`, and the `DB_*` settings (MySQL works as well as SQLite).
2. `composer install --no-dev --optimize-autoloader`
3. `php artisan migrate --force`
4. `php artisan config:cache && php artisan route:cache && php artisan view:cache`

Point the web server at `public/`.
