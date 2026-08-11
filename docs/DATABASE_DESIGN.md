# Digital Free Library — MySQL Database Design

## 1. Overview

The database stores the free book catalog, user accounts, personal reading data, and content-management data for the Digital Free Library platform. It uses the InnoDB storage engine with UTF-8 (utf8mb4) collation to support multilingual content.

**Naming conventions**
- Tables: plural, lowercase, snake_case.
- Columns: lowercase, snake_case; PK columns named `id` unless part of a composite key.
- Foreign keys named after the referenced table: `<table>_id`.
- Timestamps: `created_at`, `updated_at`, `published_at`.

**Enumerated values** (stored as MySQL ENUM) are defined inline with each table.

## 2. Tables, Fields, and Keys

### 2.1 `users`

Stores all registered readers, librarians, and administrators.

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| email | VARCHAR(255) | NOT NULL, UNIQUE |
| password_hash | VARCHAR(255) | NOT NULL |
| display_name | VARCHAR(120) | NOT NULL |
| role | ENUM('reader','librarian','admin') | NOT NULL, DEFAULT 'reader' |
| avatar_url | VARCHAR(500) | NULL |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 |
| is_verified | TINYINT(1) | NOT NULL, DEFAULT 0 |
| last_login_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | NOT NULL, ON UPDATE CURRENT_TIMESTAMP |

### 2.2 `authors`

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| name | VARCHAR(255) | NOT NULL |
| bio | TEXT | NULL |
| birth_year | SMALLINT | NULL |
| death_year | SMALLINT | NULL |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 2.3 `categories`

Self-referencing tree for catalog categories.

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| parent_id | INT UNSIGNED | NULL, FK → categories.id |
| name | VARCHAR(120) | NOT NULL |
| slug | VARCHAR(140) | NOT NULL, UNIQUE |
| description | TEXT | NULL |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 2.4 `license_types`

Reference table for allowed content licenses (legal eligibility).

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| code | VARCHAR(40) | NOT NULL, UNIQUE |
| name | VARCHAR(120) | NOT NULL |
| description | TEXT | NULL |

Seeded values: `public_domain`, `cc0`, `cc-by`, `cc-by-sa`, `gfdl`, `author_permission`.

### 2.5 `books`

Core catalog entity.

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| title | VARCHAR(255) | NOT NULL |
| slug | VARCHAR(280) | NOT NULL, UNIQUE |
| synopsis | TEXT | NULL |
| language | CHAR(2) | NOT NULL, DEFAULT 'en' |
| page_count | INT UNSIGNED | NULL |
| word_count | INT UNSIGNED | NULL |
| cover_image_url | VARCHAR(500) | NULL |
| license_type_id | INT UNSIGNED | NOT NULL, FK → license_types.id |
| rights_source | VARCHAR(500) | NULL |
| status | ENUM('draft','active','deactivated') | NOT NULL, DEFAULT 'draft' |
| view_count | INT UNSIGNED | NOT NULL, DEFAULT 0 |
| created_by | INT UNSIGNED | NOT NULL, FK → users.id |
| published_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | NOT NULL, ON UPDATE CURRENT_TIMESTAMP |

### 2.6 `book_authors`

Many-to-many between books and authors.

| Field | Type | Constraints |
| --- | --- | --- |
| book_id | INT UNSIGNED | PK, FK → books.id |
| author_id | INT UNSIGNED | PK, FK → authors.id |
| sort_order | SMALLINT | NOT NULL, DEFAULT 0 |

### 2.7 `book_categories`

Many-to-many between books and categories.

| Field | Type | Constraints |
| --- | --- | --- |
| book_id | INT UNSIGNED | PK, FK → books.id |
| category_id | INT UNSIGNED | PK, FK → categories.id |

### 2.8 `editions`

Files/formats available per book (e.g., HTML reader source, EPUB, PDF).

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| book_id | INT UNSIGNED | NOT NULL, FK → books.id |
| format | ENUM('html','epub','pdf','text') | NOT NULL |
| file_url | VARCHAR(500) | NOT NULL |
| file_size_bytes | BIGINT UNSIGNED | NULL |
| checksum | CHAR(64) | NULL |
| is_active | TINYINT(1) | NOT NULL, DEFAULT 1 |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 2.9 `collections`

Curated, featured groupings (e.g., "Classic Literature").

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| name | VARCHAR(120) | NOT NULL |
| slug | VARCHAR(140) | NOT NULL, UNIQUE |
| description | TEXT | NULL |
| cover_image_url | VARCHAR(500) | NULL |
| is_featured | TINYINT(1) | NOT NULL, DEFAULT 0 |
| created_by | INT UNSIGNED | NOT NULL, FK → users.id |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 2.10 `collection_books`

Many-to-many between collections and books.

| Field | Type | Constraints |
| --- | --- | --- |
| collection_id | INT UNSIGNED | PK, FK → collections.id |
| book_id | INT UNSIGNED | PK, FK → books.id |
| sort_order | SMALLINT | NOT NULL, DEFAULT 0 |

### 2.11 `favorites`

Registered readers' bookshelf.

| Field | Type | Constraints |
| --- | --- | --- |
| user_id | INT UNSIGNED | PK, FK → users.id |
| book_id | INT UNSIGNED | PK, FK → books.id |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 2.12 `reading_progress`

Reading history and last position (one row per user per book).

| Field | Type | Constraints |
| --- | --- | --- |
| user_id | INT UNSIGNED | PK, FK → users.id |
| book_id | INT UNSIGNED | PK, FK → books.id |
| chapter_index | INT UNSIGNED | NOT NULL, DEFAULT 0 |
| position | INT UNSIGNED | NOT NULL, DEFAULT 0 |
| is_finished | TINYINT(1) | NOT NULL, DEFAULT 0 |
| updated_at | TIMESTAMP | NOT NULL, ON UPDATE CURRENT_TIMESTAMP |

### 2.13 `reviews`

One review per user per book (rating is required; text optional).

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| user_id | INT UNSIGNED | NOT NULL, FK → users.id |
| book_id | INT UNSIGNED | NOT NULL, FK → books.id |
| rating | TINYINT UNSIGNED | NOT NULL, CHECK (rating BETWEEN 1 AND 5) |
| review_text | VARCHAR(2000) | NULL |
| status | ENUM('pending','approved','rejected') | NOT NULL, DEFAULT 'pending' |
| moderated_by | INT UNSIGNED | NULL, FK → users.id |
| moderated_at | TIMESTAMP | NULL |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | NOT NULL, ON UPDATE CURRENT_TIMESTAMP |

UNIQUE constraint: `(user_id, book_id)`.

### 2.14 `reports`

User reports on reviews, books, or other users. Polymorphic reference: `entity_type` + `entity_id` (no FK to a single table).

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| reporter_id | INT UNSIGNED | NOT NULL, FK → users.id |
| entity_type | ENUM('review','book','user') | NOT NULL |
| entity_id | INT UNSIGNED | NOT NULL |
| reason | VARCHAR(500) | NOT NULL |
| status | ENUM('open','reviewed','dismissed','resolved') | NOT NULL, DEFAULT 'open' |
| handled_by | INT UNSIGNED | NULL, FK → users.id |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |
| updated_at | TIMESTAMP | NOT NULL, ON UPDATE CURRENT_TIMESTAMP |

### 2.15 `notifications`

User-facing notifications (e.g., new titles in subscribed categories).

| Field | Type | Constraints |
| --- | --- | --- |
| id | INT UNSIGNED | PK, AUTO_INCREMENT |
| user_id | INT UNSIGNED | NOT NULL, FK → users.id |
| type | VARCHAR(40) | NOT NULL |
| title | VARCHAR(255) | NOT NULL |
| body | TEXT | NULL |
| book_id | INT UNSIGNED | NULL, FK → books.id |
| is_read | TINYINT(1) | NOT NULL, DEFAULT 0 |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

### 2.16 `audit_logs`

Immutable administrative action trail.

| Field | Type | Constraints |
| --- | --- | --- |
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT |
| user_id | INT UNSIGNED | NULL, FK → users.id |
| action | VARCHAR(80) | NOT NULL |
| entity_type | VARCHAR(40) | NOT NULL |
| entity_id | INT UNSIGNED | NULL |
| details | JSON | NULL |
| ip_address | VARCHAR(45) | NULL |
| created_at | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP |

## 3. Relationships

- `users` 1—N `favorites` N—1 `books`
- `users` 1—N `reading_progress` N—1 `books`
- `users` 1—N `reviews` N—1 `books` (at most one review per user/book)
- `users` 1—N `reports` (reporter), 1—N `reports.handled_by` (moderator)
- `users` 1—N `notifications` (0—1 `books`)
- `users` 1—N `audit_logs`
- `license_types` 1—N `books`
- `books` N—M `authors` via `book_authors`
- `books` N—M `categories` via `book_categories`
- `books` 1—N `editions`
- `collections` 1—N `books` via `collection_books`
- `categories` self-referencing parent (1—N)
- `books.created_by` → `users.id` (librarian/admin who created the record)

## 4. Index Recommendations

| Table | Index | Type | Purpose |
| --- | --- | --- | --- |
| users | `email` | UNIQUE | Login lookup (already unique) |
| users | `(role, is_active)` | BTREE | Role-based filtering/administration |
| books | `title` | BTREE | Prefix search / alphabetical sort |
| books | `(status, published_at)` | BTREE | Catalog listing of active books |
| books | `language` | BTREE | Language filtering |
| books | `license_type_id` | BTREE | Legal validation & reporting |
| books | `FULLTEXT(title, synopsis)` | FULLTEXT | Keyword search (MySQL InnoDB) |
| books | `slug` | UNIQUE | Canonical URL lookup |
| book_authors | `author_id` | BTREE | Reverse lookup (author's books) |
| book_categories | `category_id` | BTREE | Category drill-down listings |
| editions | `book_id` | BTREE | Fetch formats for a book |
| collection_books | `book_id` | BTREE | Books appearing in collections |
| favorites | `book_id` | BTREE | Popularity counting / reverse list |
| reading_progress | `(book_id, updated_at)` | BTREE | Recently-read ordering per book |
| reviews | `(book_id, status)` | BTREE | Pending review count & listing |
| reviews | `(book_id, rating)` | BTREE | Average rating computation |
| reports | `(status, created_at)` | BTREE | Moderation queue ordering |
| notifications | `(user_id, is_read)` | BTREE | Unread badge queries |
| audit_logs | `(entity_type, entity_id)` | BTREE | History lookup per entity |

**Notes**
- Composite PKs on join tables double as covering indexes for the leading column.
- FULLTEXT replaces a LIKE-based search in the MVP; fall back to a dedicated search engine (e.g., Elasticsearch) in future scope.
- `position` and `chapter_index` in `reading_progress` are opaque counters; no index beyond the PK composite is required.
- Defer `ON DELETE` handling to application logic: soft-deactivate (`status`) instead of hard deletes to preserve history, with `ON DELETE RESTRICT` on most FKs.

## 5. Mermaid ER Diagram

```mermaid
erDiagram
    USERS ||--o{ FAVORITES : "creates"
    USERS ||--o{ READING_PROGRESS : "tracks"
    USERS ||--o{ REVIEWS : "writes"
    USERS ||--o{ REPORTS : "submits"
    USERS ||--o{ NOTIFICATIONS : "receives"
    USERS ||--o{ AUDIT_LOGS : "performs"
    BOOKS ||--o{ FAVORITES : "is favorited"
    BOOKS ||--o{ READING_PROGRESS : "is read"
    BOOKS ||--o{ REVIEWS : "receives"
    BOOKS ||--o{ EDITIONS : "has"
    BOOKS ||--o{ BOOK_AUTHORS : "written by"
    AUTHORS ||--o{ BOOK_AUTHORS : "authors"
    BOOKS ||--o{ BOOK_CATEGORIES : "tagged"
    CATEGORIES ||--o{ BOOK_CATEGORIES : "contains"
    BOOKS ||--o{ COLLECTION_BOOKS : "listed in"
    COLLECTIONS ||--o{ COLLECTION_BOOKS : "groups"
    LICENSE_TYPES ||--o{ BOOKS : "licenses"
    USERS ||--o{ BOOKS : "created_by"

    USERS {
        INT UNSIGNED id PK
        VARCHAR email
        VARCHAR password_hash
        VARCHAR display_name
        ENUM role
        TINYINT is_active
        TINYINT is_verified
        TIMESTAMP created_at
    }
    AUTHORS {
        INT UNSIGNED id PK
        VARCHAR name
        TEXT bio
        SMALLINT birth_year
        SMALLINT death_year
    }
    CATEGORIES {
        INT UNSIGNED id PK
        INT UNSIGNED parent_id FK
        VARCHAR name
        VARCHAR slug
    }
    LICENSE_TYPES {
        INT UNSIGNED id PK
        VARCHAR code
        VARCHAR name
    }
    BOOKS {
        INT UNSIGNED id PK
        VARCHAR title
        VARCHAR slug
        TEXT synopsis
        CHAR language
        INT license_type_id FK
        ENUM status
        INT created_by FK
        TIMESTAMP published_at
    }
    BOOK_AUTHORS {
        INT UNSIGNED book_id PK, FK
        INT UNSIGNED author_id PK, FK
        SMALLINT sort_order
    }
    BOOK_CATEGORIES {
        INT UNSIGNED book_id PK, FK
        INT UNSIGNED category_id PK, FK
    }
    EDITIONS {
        INT UNSIGNED id PK
        INT UNSIGNED book_id FK
        ENUM format
        VARCHAR file_url
        BIGINT file_size_bytes
    }
    COLLECTIONS {
        INT UNSIGNED id PK
        VARCHAR name
        VARCHAR slug
        TINYINT is_featured
        INT created_by FK
    }
    COLLECTION_BOOKS {
        INT UNSIGNED collection_id PK, FK
        INT UNSIGNED book_id PK, FK
        SMALLINT sort_order
    }
    FAVORITES {
        INT UNSIGNED user_id PK, FK
        INT UNSIGNED book_id PK, FK
        TIMESTAMP created_at
    }
    READING_PROGRESS {
        INT UNSIGNED user_id PK, FK
        INT UNSIGNED book_id PK, FK
        INT chapter_index
        INT position
        TINYINT is_finished
    }
    REVIEWS {
        INT UNSIGNED id PK
        INT UNSIGNED user_id FK
        INT UNSIGNED book_id FK
        TINYINT rating
        VARCHAR review_text
        ENUM status
        INT moderated_by FK
    }
    REPORTS {
        INT UNSIGNED id PK
        INT reporter_id FK
        ENUM entity_type
        INT entity_id
        ENUM status
        INT handled_by FK
    }
    NOTIFICATIONS {
        INT UNSIGNED id PK
        INT user_id FK
        VARCHAR type
        VARCHAR title
        INT book_id FK
        TINYINT is_read
    }
    AUDIT_LOGS {
        BIGINT UNSIGNED id PK
        INT user_id FK
        VARCHAR action
        VARCHAR entity_type
        JSON details
    }
```
