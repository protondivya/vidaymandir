# Digital Free Library

A web-based platform providing free, unrestricted access to digital books and reading materials. The project uses a modern frontend-backend separated architecture: a React SPA serves the reading experience and a Laravel API manages the catalog, user accounts, and usage analytics.

## Repository Layout

```
.
├── backend/    # Laravel 12 JSON API (auth, catalog, favorites, analytics)
├── frontend/   # React 19 + Vite + TypeScript SPA
└── docs/       # Project spec, API design, database design, structure notes
```

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Laravel 12 (PHP 8.2+), Laravel Sanctum, SQLite (dev) / MySQL 8 (prod) |
| Frontend | React 19, TypeScript, Vite 8, Tailwind CSS v4, React Query, Zustand, react-hook-form + zod, React Router |
| Auth | Email + password, Sanctum personal access tokens, email verification, password reset |

## Prerequisites

- PHP 8.2+ with extensions: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `session`, `tokenizer`, `xml`, and `pdo_sqlite` (or `pdo_mysql`)
- Composer 2.x
- Node.js 20+ and npm 10+
- SQLite (local dev) or MySQL 8 (production)

## Backend Setup

```bash
cd backend

# Install PHP dependencies
composer install

# Create .env and generate the application key
cp .env.example .env
php artisan key:generate

# Run migrations (SQLite is used for local dev; see .env.example for MySQL)
php artisan migrate --force

# Start the API server on http://localhost:8000
php artisan serve
```

Optional steps:

```bash
# Seed the database
php artisan db:seed

# Run the test suite
php artisan test

# Run a specific test suite (tests/Unit does not exist yet in this repo)
php artisan test --testsuite=Feature
```

> Note: `backend/phpunit.xml` declares a `tests/Unit` suite that is not present yet, so plain `php artisan test` reports the directory as missing. Run with `--testsuite=Feature` until unit tests are added.

## Frontend Setup

```bash
cd frontend

# Install npm dependencies
npm install

# Start the dev server on http://localhost:5173
npm run dev

# Build for production
npm run build

# Lint
npm run lint
```

The frontend dev server proxies `/api` requests to `http://localhost:8000`, so both servers must be running for end-to-end use. See `frontend/vite.config.ts`.

## Running Locally (both servers)

Terminal 1 — API:

```bash
cd backend && php artisan serve
```

Terminal 2 — SPA:

```bash
cd frontend && npm run dev
```

Open http://localhost:5173 in your browser. The dev server is preconfigured with `allowedHosts` for `*.monkeycode-ai.live` preview domains.

## Environment Variables

Key variables live in `backend/.env` (see `backend/.env.example` for the full list):

| Variable | Purpose | Default (dev) |
| --- | --- | --- |
| `APP_KEY` | Laravel application key | generated via `php artisan key:generate` |
| `DB_CONNECTION` | Database driver | `sqlite` (MySQL for prod) |
| `FRONTEND_URL` | Allowed SPA origin for CORS / links | `http://localhost:5173` |
| `MAIL_MAILER` | Mail driver | `log` |
| `SANCTUM_TOKEN_EXPIRATION_MINUTES` | API token lifetime | `60` |

## API

The API is served under `api/v1`. Main auth endpoints:

| Method | Endpoint | Description |
| --- | --- | --- |
| POST | `/api/v1/auth/register` | Register a new user |
| POST | `/api/v1/auth/login` | Login, returns `{ access_token, user }` |
| GET | `/api/v1/auth/me` | Current authenticated user |
| POST | `/api/v1/auth/logout` | Revoke the current token |
| POST | `/api/v1/auth/password/reset` | Send a password reset link |
| POST | `/api/v1/auth/password/reset/confirm` | Reset the password |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | Verify email via signed URL |

See `docs/API_DESIGN.md` and `docs/DATABASE_DESIGN.md` for details.

## Documentation

- `docs/PROJECT_SPEC.md` — product specification and scope
- `docs/API_DESIGN.md` — API contracts
- `docs/DATABASE_DESIGN.md` — schema design
- `docs/FRONTEND_STRUCTURE.md` — SPA structure
- `docs/PROJECT_STRUCTURE.md` — backend architecture and conventions

## Development Workflow

- Conventional commit prefixes: `feat:`, `fix:`, `chore:`, `refactor:`
- Backend quality gates: `vendor/bin/pint --test`, PHPStan (when configured), `php artisan test`
- Frontend quality gate: `npm run lint`
