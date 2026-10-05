# Disrupt Cyprus

Bilingual (Greek/English) personalized startup & innovation news for Cyprus and the Eastern Mediterranean:
React PWA + Laravel API + Filament admin. See `docs/ARCHITECTURE.md` for the big picture.

## Local setup (≈10 minutes)

Requirements: Podman with `podman-compose` (or Docker with Compose on Linux) and Git.
Everything — PHP 8.4, Composer, MariaDB 10.11, Nginx, Mailpit — runs in containers, pinned to the production versions.
Production runs on Apache + PHP-FPM + MariaDB without Docker: see `docs/DEPLOYMENT.md`.

```bash
# 1. Start the stack (first run builds the PHP image; a few minutes)
podman compose up -d --build

# 2. Configure and install backend dependencies
cp backend/.env.example backend/.env
podman compose exec app composer install
podman compose exec app php artisan key:generate
podman compose exec app php artisan storage:link

# 3. Create the schema and demo content
podman compose exec app php artisan migrate --seed

# 4. Restart the long-running workers so they load the fresh code
podman compose restart queue scheduler
```

| URL | What |
|---|---|
| http://localhost:8080/api/v1/industries | API |
| http://localhost:8025 | Mailpit (all outgoing mail) |
| http://localhost:5173 | PWA dev server (from Phase 4) |
| http://localhost:8080/admin | Filament admin (from Phase 3) |

Demo accounts (password `password`): `admin@disruptcyprus.test`, `editor@disruptcyprus.test`, `reader@disruptcyprus.test`.

### Notes
- Services use host networking bound to `127.0.0.1` (rootless podman bridge networking fails on some Fedora setups). MariaDB listens on **3307**.
- Docker users: delete the `userns_mode: keep-id` lines in `docker-compose.yml`.
- After `composer require` or changes to service providers, run `podman compose restart queue scheduler`.

## Tests & quality

```bash
podman compose exec app php artisan test          # Pest (MariaDB disrupt_testing database)
podman compose exec app vendor/bin/pint           # code style
podman compose exec app vendor/bin/phpstan analyse --memory-limit=1G   # Larastan level 6
```
