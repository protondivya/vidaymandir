# Digital Free Library — REST API Design (MVP)

## 1. General Conventions

- **Base URL**: `/api/v1` (frontend proxies to the backend server).
- **Format**: JSON request/response bodies; UTF-8.
- **Authentication**: `Authorization: Bearer <access_token>` header. Roles: `reader`, `librarian`, `admin`.
- **Access token**: short-lived JWT (e.g., 15 min). **Refresh token**: long-lived (e.g., 7 days), rotating, stored server-side.
- **Pagination**: list endpoints accept `page` (1-based) and `limit` (default 20, max 100); responses include `data`, `meta` (total, page, limit).
- **Standard errors**: `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `422 Validation Error` (field errors), `429 Too Many Requests`, `500 Internal Server Error`.
- **List/sort conventions**: `sort` values e.g. `newest`, `popular`, `title_asc`, `rating_desc`.
- Unless stated, all response times reference `books` entries in the compact catalog shape: `id, title, slug, authors, language, cover_image_url, rating, published_at`.

## 2. Authentication

| Method | Endpoint | Auth | Request | Response |
| --- | --- | --- | --- | --- |
| POST | `/auth/register` | None | `{email, password, display_name}` | `201` `{user, message: "verification email sent"}` |
| POST | `/auth/login` | None | `{email, password}` | `200` `{access_token, refresh_token, user}` |
| POST | `/auth/refresh` | None | `{refresh_token}` | `200` `{access_token, refresh_token}` (rotates) |
| POST | `/auth/logout` | Bearer | `{refresh_token}` | `204` No Content (revokes token) |
| GET | `/auth/verify-email` | None | query `{token}` | `200` `{message}` |
| POST | `/auth/password/reset` | None | `{email}` | `200` `{message}` (always 200 if exists) |
| POST | `/auth/password/reset/confirm` | None | `{token, new_password}` | `200` `{message}` |

**Rules**
- Registration creates a `reader` account only; role changes are admin-only.
- Login rate-limited to prevent brute force; email verification required to activate favorites/reviews.
- Refresh token rotation invalidates the previously issued token.

## 3. Books

| Method | Endpoint | Auth | Request | Response |
| --- | --- | --- | --- | --- |
| GET | `/books` | None | query `{q, category, language, sort, page, limit}` | `200` paginated catalog list |
| GET | `/books/{id}` | None | path `{id}` (numeric id or slug) | `200` full book detail |
| GET | `/books/{id}/reviews` | None | query `{page, limit}` | `200` approved reviews only |
| POST | `/books/{id}/reviews` | Bearer (reader+) | `{rating, review_text?}` | `201` created review (status `pending`) |
| PUT | `/reviews/{review_id}` | Bearer (owner) | `{rating, review_text?}` | `200` updated review |
| DELETE | `/reviews/{review_id}` | Bearer (owner / admin) | — | `204` No Content |
| POST | `/books` | Bearer (librarian+) | `{title, language, license_type_id, rights_source?, synopsis?, cover_image_url?, authors[], categories[], editions[]}` | `201` created book (status `draft`) |
| PUT | `/books/{id}` | Bearer (librarian+) | any subset of book fields | `200` updated book |
| DELETE | `/books/{id}` | Bearer (librarian+) | — | `204` No Content (soft: status `deactivated`) |
| POST | `/books/import` | Bearer (librarian+) | `multipart/form-data` CSV/JSON file | `202` import job accepted |

**Rules**
- Public `/books` list returns only `status = active`.
- Keyword search `q` matches title, author, and synopsis (FULLTEXT).
- Rating returned is the aggregate of approved reviews only.
- One review per user per book; duplicate returns `422`.
- `books/import` runs asynchronously; client polls a job status endpoint.

## 4. Categories

| Method | Endpoint | Auth | Request | Response |
| --- | --- | --- | --- | --- |
| GET | `/categories` | None | — | `200` nested category tree (with book counts) |
| GET | `/categories/{slug}/books` | None | query `{language, sort, page, limit}` | `200` paginated books (inherits descendants) |
| POST | `/categories` | Bearer (librarian+) | `{name, slug, parent_id?, description?}` | `201` created category |
| PUT | `/categories/{id}` | Bearer (librarian+) | any subset of category fields | `200` updated category |
| DELETE | `/categories/{id}` | Bearer (admin) | — | `204` No Content (blocked if children or books exist) |

## 5. Downloads

| Method | Endpoint | Auth | Request | Response |
| --- | --- | --- | --- | --- |
| GET | `/books/{id}/editions` | None | — | `200` available formats `{format, size_bytes}` |
| GET | `/books/{id}/read` | None | — | `200` HTML/TOC content for the online reader |
| GET | `/books/{id}/download/{format}` | None | path `{format}` in `epub, pdf, text` | `302` redirect to pre-signed file URL (or `200` stream) |
| GET | `/collections` | None | — | `200` featured collections |

**Rules**
- All download/read routes validate `status = active` and edition `is_active = 1`; otherwise `404`.
- File URLs are pre-signed and time-limited (e.g., 15 minutes) to avoid hotlinking.
- `/read` requires no auth (guest reading); a reading-position save is sent to the Profile endpoints when authenticated.
- Downloads increment `books.view_count` only once per client session to avoid inflation.

## 6. Favorites

| Method | Endpoint | Auth | Request | Response |
| --- | --- | --- | --- | --- |
| GET | `/favorites` | Bearer (reader+) | query `{page, limit}` | `200` paginated favorites list |
| POST | `/books/{id}/favorite` | Bearer (reader+) | — | `201` added (idempotent; `200` if already present) |
| DELETE | `/books/{id}/favorite` | Bearer (reader+) | — | `204` No Content (idempotent) |
| GET | `/books/{id}` | Bearer (reader+) | — | detail response additionally includes `is_favorited` |

**Rules**
- Favorites require a verified email; unverified users get `403`.
- Idempotent add/remove; no error on duplicate or absent entries.
- Favorites are private to the owning user.

## 7. Profile

| Method | Endpoint | Auth | Request | Response |
| --- | --- | --- | --- | --- |
| GET | `/profile` | Bearer (reader+) | — | `200` `{id, email, display_name, role, avatar_url, is_verified, created_at}` |
| PUT | `/profile` | Bearer (reader+) | `{display_name?, avatar_url?, preferred_language?}` | `200` updated profile |
| PUT | `/profile/password` | Bearer (reader+) | `{current_password, new_password}` | `200` `{message}` (refresh tokens revoked) |
| GET | `/profile/reading-history` | Bearer (reader+) | query `{page, limit}` | `200` recent books with last position and progress |
| PUT | `/profile/reading-progress/{bookId}` | Bearer (reader+) | `{chapter_index, position, is_finished?}` | `200` saved progress |
| DELETE | `/profile` | Bearer (reader+) | `{password}` | `202` account deletion scheduled |

**Rules**
- Only the authenticated user may access their own profile; admin may view via user management (admin-only endpoints).
- Reading progress is upserted (one row per user/book).
- Password change invalidates all outstanding refresh tokens for the account.
- Account deletion anonymizes or removes personal data per the privacy policy; content created by the user is retained in audit logs.

## 8. Administrative Endpoints (Summary)

Management endpoints used by Librarian/Admin roles (referenced above, not expanded):

- Users: `GET /users`, `GET /users/{id}`, `PUT /users/{id}/role` (admin), `PUT /users/{id}/status` (admin).
- Moderation: `GET /reviews?status=pending` (librarian+), `PUT /reviews/{id}/moderate` (approve/reject), `GET /reports?status=open` (librarian+), `PUT /reports/{id}` (resolve/dismiss).
- Collections: `POST/PUT/DELETE /collections` and `POST /collections/{id}/books` (librarian+).
- Analytics: `GET /admin/analytics/overview` (librarian+).
- Audit: `GET /admin/audit-logs` (admin).

## 9. Authentication Matrix

| Capability | Guest | Reader | Librarian | Admin |
| --- | --- | --- | --- | --- |
| Browse, search, read, download | Yes | Yes | Yes | Yes |
| Register / login | Yes | Yes | Yes | Yes |
| Favorites, reviews, profile, reading progress | No | Yes | Yes | Yes |
| Create/edit books, categories, collections | No | No | Yes | Yes |
| User & role management, audit logs | No | No | No | Yes |
