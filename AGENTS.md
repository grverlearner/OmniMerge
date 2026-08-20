# AGENTS.md

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+, Eloquent ORM, MySQL (production), SQLite in-memory (tests)
- **Frontend**: Blade templates, Tailwind CSS, Alpine.js, Vite
- **Auth**: Laravel Breeze (registration, login, password reset, email verification)
- **Tests**: PHPUnit 11

## Commands

```bash
# Full setup (install deps, .env, key, migrate, build frontend)
composer setup

# Dev server (runs artisan serve + queue:listen + pail + vite concurrently)
composer dev

# Tests (clears config cache, then runs phpunit)
composer test
# or directly:
php artisan test

# Run a single test file
php artisan test --filter=EntityManagementTest
php artisan test tests/Feature/EntityManagementTest.php

# Run a single test method
php artisan test --filter=test_can_create_entity

# Frontend only
npm run dev    # Vite dev server
npm run build  # Production build

# Migrations
php artisan migrate
php artisan migrate:status
# NEVER use migrate:fresh on a database with real data

# Storage symlink (needed for image uploads)
php artisan storage:link

# Clear all caches
php artisan optimize:clear
```

## Architecture

**Request flow**: Route -> Controller -> Form Request / Policy -> Service -> Model -> DB -> Blade view

**Key layers**:
- `app/Http/Controllers/` — organized by domain (Entities/, Attributes/, Versions/, Collections/, Community/, Tournaments/)
- `app/Http/Requests/` — form validation
- `app/Policies/` — authorization (per-model, all resources are user-owned)
- `app/Services/` — business logic, organized in `Attributes/`, `Entities/`, `Versions/`, `Community/`, `Tournaments/`
- `app/Models/` — Eloquent models with SoftDeletes on most entities
- `resources/views/` — Blade templates mirroring controller structure

**All authenticated routes** are in `routes/web.php` under the `auth` middleware group. The `/hub` route is the main navigation hub.

## Domain Model

The core domain is a **dynamic entity system** — users define their own entity types, attributes, catalogs, and versions:

- `Entity` — the canonical record (never replaced by versions)
- `EntityType` — user-defined categories (e.g., Character, Vehicle)
- `Attribute` — dynamic fields (TEXT, NUMBER, BOOLEAN, DATE, OPTION types)
- `AttributeOption` — catalog values for OPTION-type attributes, supports hierarchy via `parent_option_id`
- `Collection` — groups of entities
- `Version` / `EntityVersion` — reusable version definitions applied to entities; supports inheritance chains
- `EntityBaseVersion` — the "active base" for display purposes (separate from resolver default and public presentation)
- `EntityPresentation` — how an entity appears publicly (independent from base version and resolver default)

**Four distinct version concepts** that must not be confused:
1. Entity original — canonical data, never disappears
2. Active base (★) — representation used in work views
3. Default resolver (⚡) — fallback for automatic resolution
4. Public presentation (◎) — community-facing representation

## Conventions

- **Language**: Code comments, route names, variable names, and README are in **Spanish**
- **Services pattern**: Complex logic lives in Services, not Controllers. Major services: `EntityBuilderService`, `VersionResolverService`, `EntityVersionService`, `EntityPresentationService`, `EntityBaseVersionService`, `AttributeContextService`
- **SoftDeletes**: Most domain models use `SoftDeletes` — deletions are logical via `deleted_at`
- **Ownership**: Every resource validates user ownership through Policies and scoped queries
- **Eager loading**: Always use `->with([...])` to avoid N+1 queries when displaying related data
- **Sequence numbers**: Many entities have a `sequence_number` field for ordering

## Tests

- Tests run against **SQLite in-memory** (configured in `phpunit.xml`), NOT MySQL
- Feature tests at `tests/Feature/`, Unit tests at `tests/Unit/`
- Tournament tests are the most extensive area (`tests/Unit/Tournaments/`, `tests/Feature/Tournaments/`)
- Core entity/attribute tests exist but coverage is partial — more needed for contexts, cloning, version inheritance, resolver, permissions

## Patch Files

The root contains `.patch` files (e.g., `OmniMerge-P3.6.1-omni-confirm-stable.patch`). These are stable checkpoint patches for incremental feature work. If restoring or reviewing previous states, check these files.
