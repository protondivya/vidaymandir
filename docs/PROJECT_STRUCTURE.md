# Digital Free Library — Laravel 12 Backend Project Structure

## 1. Technology Stack

| Concern | Choice |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL 8 (InnoDB, utf8mb4) |
| API auth | Laravel Sanctum (personal access tokens) |
| API docs | Scribe / OpenAPI (annotations-driven) |
| Code style | Laravel Pint (PSR-12) + PHP CS Fixer preset |
| Static analysis | PHPStan (level 6, `larastan/larastan`) |
| Testing | Pest / PHPUnit |
| Search | MySQL FULLTEXT (MVP); Elasticsearch deferred to future scope |

The backend is **API-only** in the MVP: the web frontend (Vue/React SPA) consumes `/api/v1` and the Laravel app serves JSON exclusively. No Blade views are needed for MVP feature work.

## 2. Folder Organization

Layered, convention-first structure built on the Laravel 12 skeleton:

```
dfl-backend/
├── app/
│   ├── Actions/                  # Single-purpose service classes (e.g., PublishBook)
│   ├── Enums/                    # Backed enums: UserRole, BookStatus, ReviewStatus, FileFormat...
│   ├── Exceptions/               # Custom exceptions mapped to HTTP errors
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── Auth/         # RegisterController, LoginController, ResetController...
│   │   │   │   ├── Book/         # BookController, ReviewController, DownloadController
│   │   │   │   ├── Category/     # CategoryController
│   │   │   │   ├── Favorite/     # FavoriteController
│   │   │   │   └── Profile/      # ProfileController, ReadingProgressController
│   │   │   └── Controller.php
│   │   ├── Middleware/           # e.g., EnsureEmailVerified, ForceJsonResponse
│   │   ├── Requests/             # Form Request classes (one per validated action)
│   │   ├── Resources/            # API Resources (UserResource, BookResource, ReviewResource)
│   │   └── Responses/            # ApiResponse helpers / success & error shapes
│   ├── Jobs/                     # Async jobs: BulkImportBooks, IncrementViewCount
│   ├── Models/                   # Eloquent models + relationships (Book, User, Author, ...)
│   ├── Notifications/            # Mail notifications: VerifyEmail, ResetPassword, NewTitleAdded
│   ├── Observers/                # e.g., ReviewObserver clears aggregate rating cache
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   ├── Services/                 # Business logic: CatalogService, ImportService, AnalyticsService
│   ├── Support/                  # Pagination helpers, pre-signed URL helper, Slugifier
│   └── Rules/                    # Custom validation rules (e.g., LicenseAllowed)
├── bootstrap/                    # app.php (middleware/exception wiring), cache/
├── config/                       # laravel + sanctum.php, scribe.php, constants
├── database/
│   ├── factories/
│   ├── migrations/               # 16 tables per docs/DATABASE_DESIGN.md
│   └── seeders/                  # LicenseTypesSeeder, RolesSeeder, DemoCatalogSeeder
├── docs/                         # API + architecture notes (optional internal)
├── public/                       # index.php, storage symlink
├── routes/
│   ├── api.php                   # /api/v1 route groups (guest / auth / librarian / admin)
│   ├── console.php
│   └── web.php                   # empty health-check route only
├── storage/
├── tests/
│   ├── Feature/                  # API feature tests per resource
│   ├── Unit/                     # Services, Actions, Enums
│   └── Pest.php / TestCase.php
├── .env.example
├── pint.json
├── phpstan.neon
└── composer.json
```

**Route organization** in `routes/api.php` uses `Route::prefix('v1')` with role middleware groups:

- `guest` group: books, categories, downloads (public read)
- `auth:sanctum` group: favorites, profile, reviews write
- `role:librarian` group: book/category/collection management, moderation
- `role:admin` group: users, audit logs

## 3. Required Packages

### Composer (runtime)

| Package | Purpose |
| --- | --- |
| `laravel/framework` | Core framework (^12) |
| `laravel/sanctum` | Token-based API authentication |
| `predis/predis` or `laravel/redis` | Cache/queue driver (sessions, rate limits) |
| `spatie/laravel-sluggable` | Canonical URL slugs for books/categories/collections |
| `spatie/laravel-medialibrary` | Book cover and edition file storage/transformations |
| `aws/aws-sdk-php` (optional) | S3-backed file storage for editions (if not local disk) |

### Composer (dev)

| Package | Purpose |
| --- | --- |
| `pestphp/pest` | Test framework (feature/unit tests) |
| `larastan/larastan` | PHPStan static analysis for Laravel |
| `laravel/pint` | PSR-12 code style fixer |
| `mockery/mockery` | Mocking (shipped with skeleton) |
| `knuckleswtf/scribe` | Generates API documentation/OpenAPI from annotations |

### NPM (docs tooling only)

| Package | Purpose |
| --- | --- |
| `@openapitools/openapi-generator-cli` (optional) | Validate/regenerate client stubs |

No frontend build tooling is installed in this package; the SPA is a separate repository.

## 4. Authentication Approach (Sanctum)

**Strategy**: token-based authentication using Sanctum **personal access tokens** (not SPA cookie sessions), because the frontend is a separate origin consuming a pure JSON API.

### Flow

1. **Register / Login**: `POST /api/v1/auth/login` verifies credentials, returns `{access_token, user}`. The access token is a Sanctum plain-text token with a configurable expiry (default 60 minutes via `token_expires_in`).
2. **Authorized requests**: client sends `Authorization: Bearer <token>`; `auth:sanctum` guard resolves the user and applies token abilities.
3. **Roles via abilities**: tokens are issued with abilities derived from the user's role (`role:reader`, `role:librarian`, `role:admin`). A custom `role` middleware checks both the authenticated role and token ability.
4. **Revocation**: `POST /auth/logout` deletes the current token from `personal_access_tokens`. Changing password revokes all tokens for the user (job iterates the token table).
5. **Verification gating**: `EnsureEmailVerified` middleware protects favorites/review/profile write endpoints; returns `403` with an explanatory payload for unverified accounts.

### Configuration points

| Item | Location | Value |
| --- | --- | --- |
| Guard | `config/auth.php` | `guards.api.driver = sanctum` |
| Token expiry | Sanctum `token_expires_in` config | e.g., `60` minutes |
| Table | Sanctum migration | `personal_access_tokens` |
| Route middleware | `bootstrap/app.php` | register `role`, `verified`, `force.json` aliases |

### Known trade-off (documented)

Sanctum does not provide native refresh-token rotation. In the MVP this is acceptable: access tokens are short-lived and refreshed by re-authenticating (or reusing a stored long-lived token for mobile). If refresh semantics are required later, wrap token issuance in an `AuthService` that pairs an access token with a stored refresh token and rotates on `POST /auth/refresh` — isolated to the auth service only.

## 5. Coding Standards

### Style (Laravel Pint)

- Adopt `laravel` preset (PSR-12 aligned) via `pint.json`.
- Enforce in CI: `vendor/bin/pint --test`; auto-fix on commit via pre-commit hook.
- Conventions: 4-space indentation, single quotes, trailing commas in multiline arrays, `declare(strict_types=1)` in every class file.

### Static analysis

- PHPStan level 6 via `larastan/larastan`; run `vendor/bin/phpstan analyse` in CI.
- No `@phpstan-ignore` without a documented reason.

### Naming and structure

- **Models**: singular, StudlyCase (`Book`, `ReadingProgress`); table names plural snake_case (default).
- **Controllers**: singular resource controllers, one per resource, thin — business logic lives in `Services` or single-purpose `Actions`.
- **Validation**: Form Request classes per action (`StoreBookRequest`, `UpdateReviewRequest`); never validate inline in controllers.
- **Responses**: JSON via `ApiResponse` helper and dedicated API `Resources`; no raw arrays of attributes in controllers.
- **Enums**: backed enums for all state fields (`BookStatus`, `UserRole`, `ReviewStatus`); enforce DB enum values via `Enum::tryFrom` casts.
- **Migrations**: snake_case class names with table + column purpose (e.g., `create_books_table`, `add_slug_to_categories_table`); define FKs with explicit `foreignId(...)->constrained()`.
- **Error handling**: use Laravel's exception renderer; map domain exceptions to `422`/`404`/`403` with consistent JSON shape `{message, errors?}`.
- **N+1 prevention**: eager-load relationships in resources; document expected `with()` in controllers.

### Testing

- Pest (preferred) with `tests/Feature` covering every public endpoint (happy path + auth/validation failures).
- Factories for all models; seeders for `license_types` and demo catalog.
- Use `RefreshDatabase` in feature tests; no reliance on live external services (email via `Mail::fake()`).

### Git and quality gates

- Conventional commit prefixes (`feat:`, `fix:`, `chore:`, `refactor:`).
- Pre-push gate: `pint --test` + `phpstan` + `pest` must pass.
- `.env.example` documents every runtime variable; secrets never committed.

## 6. Environment Configuration (`.env.example` keys)

```
APP_NAME=Digital Free Library
APP_ENV=local
APP_KEY=
APP_URL=
APP_TIMEZONE=UTC
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dfl_backend
DB_USERNAME=
DB_PASSWORD=
MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@dfl.local
SANCTUM_TOKEN_EXPIRES_MIN=60
FILESYSTEM_DISK=local
STORAGE_PRE_SIGN_URLS=0
RATE_LIMIT_GUEST=60,1
RATE_LIMIT_AUTHED=120,1
```

## 7. Setup Sequence (reference)

1. `composer create-project laravel/laravel dfl-backend`
2. `composer require laravel/sanctum spatie/laravel-sluggable spatie/laravel-medialibrary`
3. `php artisan install:api` (publishes Sanctum migration + `routes/api.php`)
4. Publish & configure Sanctum, Scribe, Pint (`pint.json`), PHPStan (`phpstan.neon`)
5. Implement migrations/seeders for all tables in `docs/DATABASE_DESIGN.md`
6. Build auth services and middleware before feature controllers (dependencies first)
