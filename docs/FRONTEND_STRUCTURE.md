# Digital Free Library — React (Vite) Frontend Project Structure

## 1. Technology Stack

| Concern | Choice |
| --- | --- |
| Build tool | Vite (React + TypeScript) |
| Framework | React 18 (SPA) |
| Styling | Tailwind CSS v4 |
| Routing | React Router v6 (declarative routes, data router) |
| State | TanStack Query (server state) + Zustand (client/UI state) |
| HTTP | Axios (single configured instance, auth interceptor) |
| Validation | Zod (schema validation shared with forms) |
| Testing | Vitest + React Testing Library |
| Linting | ESLint (typescript + react-hooks presets) + Prettier |

The SPA is a separate repository from the Laravel backend and consumes `/api/v1` through a Vite dev-server reverse proxy.

## 2. Folder Organization

Feature-first structure with shared/global folders for cross-cutting concerns:

```
dfl-frontend/
├── public/
│   ├── favicon.svg
│   └── covers/                # static fallback assets
├── src/
│   ├── api/                   # API layer (one module per resource)
│   │   ├── client.ts          # Axios instance, baseURL /api/v1, interceptors
│   │   ├── auth.ts
│   │   ├── books.ts
│   │   ├── categories.ts
│   │   ├── downloads.ts
│   │   ├── favorites.ts
│   │   └── profile.ts
│   ├── app/                   # App shell wiring
│   │   ├── router.tsx         # route definitions
│   │   ├── root.tsx           # root component (providers + RouterProvider)
│   │   └── providers.tsx      # QueryClientProvider, AuthProvider
│   ├── components/
│   │   ├── ui/                # presentational primitives (Button, Badge, Spinner)
│   │   └── shared/            # cross-feature pieces (RatingStars, BookCard, EmptyState)
│   ├── features/
│   │   ├── auth/              # login, register, reset password
│   │   ├── books/             # catalog, detail, reader, reviews
│   │   ├── categories/        # category tree + listing
│   │   ├── downloads/         # editions, format download
│   │   ├── favorites/         # favorites list
│   │   ├── profile/           # profile page, reading history
│   │   ├── collections/       # featured collections (home)
│   │   └── admin/             # librarian/admin management screens
│   ├── hooks/                 # shared hooks (useAuth, useDebounce, usePagination)
│   ├── layouts/               # page layout components (see Section 3)
│   ├── lib/                   # pure utilities (formatDate, slugify, auth-storage)
│   ├── stores/                # Zustand stores (see Section 4)
│   ├── types/                 # shared TS types / Zod schemas mirroring API
│   └── main.tsx
├── index.html
├── vite.config.ts             # proxy + allowedHosts (see Section 5)
├── tailwind.config.js / src/index.css
├── tsconfig.json
├── eslint.config.js
└── package.json
```

**Feature folder convention**: each `features/<name>/` contains its own `components/`, `hooks/`, `api.ts` (or re-exports from `src/api`), and `types.ts`. Feature folders do not import from other feature folders directly; shared code lives in `src/components` and `src/lib`.

## 3. Routing

React Router v6 with a **data router** (`createBrowserRouter`) and route-level code splitting via `lazy()`.

### Route map

| Path | Auth | Layout | Purpose |
| --- | --- | --- | --- |
| `/` | Public | `PublicLayout` | Home: featured collections + recently added |
| `/books` | Public | `PublicLayout` | Catalog listing + search/filters |
| `/books/:slug` | Public | `PublicLayout` | Book detail |
| `/books/:slug/read` | Public | `ReaderLayout` | Online reader (guest-friendly) |
| `/categories/:slug` | Public | `PublicLayout` | Category book listing |
| `/login` | Guest | `AuthLayout` | Login |
| `/register` | Guest | `AuthLayout` | Register |
| `/reset-password` | Guest | `AuthLayout` | Password reset |
| `/favorites` | Reader | `AppLayout` | Personal bookshelf |
| `/profile` | Reader | `AppLayout` | Profile + reading history |
| `/admin/*` | Librarian/Admin | `AppLayout` | Management screens |

### Conventions

- **Route guards**: a `<RequireAuth>` and `<RequireRole>` wrapper components around protected layouts; redirect to `/login` with a `redirect` query on the current location.
- **Loaders**: catalog/category/detail routes use router `loader` functions that prefetch with TanStack Query (`queryClient.fetchQuery`) so data is available before render.
- **404**: catch-all route rendering a `NotFound` page inside `PublicLayout`.
- **Lazy loading**: every route is lazy-loaded; `Suspense` fallback renders a global `PageSpinner`.
- **Scroll restoration**: `ScrollRestoration` component on the root route.

## 4. Layouts

Four layout components in `src/layouts/`, each composed from `components/ui` primitives:

| Layout | Contents | Used by |
| --- | --- | --- |
| `PublicLayout` | Header (logo, search, nav, auth buttons), main outlet, footer | All public pages |
| `AuthLayout` | Centered card shell, brand mark, minimal chrome | Login/register/reset |
| `AppLayout` | Authenticated header (avatar menu, favorites), main outlet, footer | Favorites, profile, admin |
| `ReaderLayout` | Minimal chrome: reader toolbar (fonts, theme, TOC), content outlet, no site header/footer | Reader route |

Layouts handle the `Out` outlet placement and wrap pages; they do not own data fetching except header-level data (auth status from the auth store).

## 5. State Management Recommendation

**Two-tier model:**

1. **Server state — TanStack Query (recommended for all API data)**
   - Each `features/*/api.ts` exposes query/mutation hooks built on `useQuery` / `useMutation` with typed keys (e.g., `['books', 'list', filters]`).
   - Query keys namespaced per resource; invalidate on mutations (`invalidatesTags`).
   - Handles caching, refetch-on-focus, pagination (`keepPreviousData`), and optimistic updates (favorites toggle).
   - `QueryClient` configured globally with sensible staleTime/retry in `app/providers.tsx`.

2. **Client/UI state — Zustand (recommended for small, shared, ephemeral state)**
   - `stores/authStore.ts`: `accessToken`, `user`, `login()`, `logout()` — persisted to `localStorage`; hydrated at app boot.
   - `stores/readerStore.ts`: reader preferences (font size, theme, last position) — persisted per device for guests.
   - `stores/uiStore.ts`: modals, toasts, mobile drawer open state — ephemeral, not persisted.

**Explicitly not used**: Redux (overhead for this scope) and context for server data (handled by TanStack Query). Form state uses `react-hook-form`; no global form state library needed.

## 6. Tailwind Setup

Tailwind CSS **v4** (Vite plugin, CSS-first configuration):

- Install: `tailwindcss` + `@tailwindcss/vite`; register plugin in `vite.config.ts`.
- Entry CSS `src/index.css`:
  - `@import "tailwindcss";`
  - `@theme` block defining the design tokens: brand color palette, font families, spacing scale, radius, shadows (aligned with the app design system).
- **Dark mode**: class-based strategy (`@custom-variant dark` with a `.dark` toggle); reader theme switch flips the `.dark` class.
- **Component classes**: small set of reusable utilities via `@layer components` for `.btn`, `.card`, `.input` to keep JSX readable.
- **No separate config file required** in v4; `tailwind.config.js` only if legacy config compatibility is needed.
- Variants used heavily: `dark:`, `focus-visible:`, `motion-safe:`; content detection is automatic (scans `src/**/*.{ts,tsx}`).

## 7. Vite Configuration

`vite.config.ts` includes the reverse proxy for the backend and the allowed-hosts entry for the preview domain:

| Setting | Value |
| --- | --- |
| `server.proxy['/api']` | `target: http://localhost:8000` (Laravel dev server), `changeOrigin: true` |
| `server.allowedHosts` | `['.monkeycode-ai.live']` |
| `build.outDir` | `dist` |
| Environment | `VITE_API_BASE_URL` for production builds (falls back to `/api/v1`) |

This keeps all requests same-origin in preview: the SPA at the exposed port proxies `/api` to the Laravel backend, avoiding CORS in development.

## 8. Coding Standards

- **TypeScript strict**: `strict: true`; no `any` without documented exception; API responses typed via Zod schemas in `src/types`.
- **Naming**: PascalCase components/files; camelCase hooks, functions, variables; feature folders lowercase.
- **File organization**: one component per file; co-locate `.test.tsx` beside components; barrel `index.ts` re-exports in feature folders.
- **Data flow**: components never call Axios directly; they use the typed hooks in `features/*/api.ts`.
- **Lint gates**: `npm run lint` (ESLint + Prettier) and `npm run test` (Vitest) must pass; enforced in pre-commit/CI.
- **API contract**: the `types/` and `api/` layers mirror `docs/API_DESIGN.md`; changes to one must be reflected in the other.

## 9. Setup Sequence (reference)

1. `npm create vite@latest dfl-frontend -- --template react-ts`
2. Install deps: `react-router-dom @tanstack/react-query zustand axios react-hook-form zod`
3. Install dev deps: `tailwindcss @tailwindcss/vite vitest @testing-library/react eslint prettier`
4. Configure `vite.config.ts` (proxy, allowedHosts), Tailwind v4 plugin, ESLint/Prettier
5. Scaffold folders: `app/`, `layouts/`, `features/`, `api/`, `components/{ui,shared}`, `stores/`, `lib/`, `types/`
6. Build the API client + auth store first (all features depend on them), then layouts, then feature routes
