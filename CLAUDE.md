
# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`telu-api` is the Laravel 13 (PHP 8.3) backend for **TELU BAOBAB**, a multi-service "super-app" targeting Togo. A single `users` table backs three distinct marketplaces, disambiguated by `users.user_type`:

- **Commerce & delivery** — `vendors` → `products` → `orders` → `order_items` → `deliveries`, fulfilled by `drivers`.
- **Real estate** — `property_owners` → `properties` → `reservations`.
- **Jobs** — `recruiters` → `job_offers`, and `job_seekers` → `job_applications`.

Cross-cutting tables: `subscriptions` (vendor plans), `payments`, `messages`, `ratings`, `notifications`.

**Implementation status.** Treat the migrations as the source of truth for the data model.
- **Done:** all 18 Eloquent models (+ `User`), a factory per model, a coherent `DatabaseSeeder`, and token auth (`Api\AuthController` — register/login/me/logout at `/api/auth/*`).
- **Still to build:** resource controllers, form requests, policies, and API resources for the domain entities. Only auth endpoints exist in `routes/api.php` so far.

Seeded test accounts (password `password`): `admin@telu.tg`, `client@telu.tg`.

## Commands

```bash
composer dev          # run server + queue worker + log tailer (pail) + vite concurrently
composer setup        # first-time: install, .env, key:gen, migrate, npm build
composer test         # clears config, then runs artisan test (PHPUnit)
php artisan test --filter=SomeTest        # run a single test class/method
php artisan test tests/Feature/Xyz.php    # run one test file
vendor/bin/pint       # format code (Laravel Pint) — run before finishing changes
php artisan migrate:fresh --seed          # rebuild the DB
```

Tests run on an in-memory SQLite DB (see `phpunit.xml`); the dev DB is file-based SQLite at `database/database.sqlite`.

## Conventions (important, non-default)

- **Laravel 13 model attributes.** Models configure themselves with PHP attributes, not properties — `#[Fillable([...])]` and `#[Hidden([...])]` on the class, and a `casts()` method (not `$casts`). Follow this style; see `app/Models/User.php`. Note its current attributes still reference the old `name`/`email` scaffolding — the real `users` schema uses `full_name`, `phone`, `email` (nullable), `user_type`, etc.

- **UUID primary keys everywhere.** Every table uses `$table->uuid('id')->primary()` and `foreignUuid(...)`. New models must use the `HasUuids` trait (keys are not auto-incrementing integers). Factories and tests must not assume integer IDs.

- **Geolocation is first-class.** `users`, `vendors`, `properties`, `job_offers`, orders, and deliveries carry `latitude`/`longitude` `decimal(10,7)` columns, several with composite indexes. Proximity/"near me" queries are a core feature.

- **Payments are polymorphic.** `payments.reference_type` is one of `order|reservation|subscription` with a `reference_id` UUID (indexed together). Payment methods are `flooz|tmoney|card` (Flooz and TMoney are Togolese mobile money — keep these).

- **Enums live in the DB.** Status/type fields are DB `enum` columns (e.g. `orders.status`, `properties.property_type`, `users.user_type`). Keep application-level enums/validation in sync with the migration definitions.

## Architecture notes

- **Auth:** Laravel Sanctum (token-based). Login accepts a single `login` field that is auto-detected as email or phone. Protect routes with `auth:sanctum`. Note `personal_access_tokens` was published with **`uuidMorphs('tokenable')`** (not the default `morphs`) because users have UUID keys — any future morph table must do the same.
- **API-only exceptions:** `bootstrap/app.php` forces JSON error rendering for `api/*` requests. Middleware and exception handling are registered there (Laravel 13 has no `app/Http/Kernel.php` or `app/Exceptions/Handler.php`).
- **Queues/mail/cache/sessions** default to the `database` driver; broadcast defaults to `log`. A queue worker is part of `composer dev`.
- **Config lookups** use PHP attributes and the streamlined Laravel 11+/13 skeleton — there is no `config/` bloat beyond the standard files; routing/middleware/scheduling are wired in `bootstrap/app.php` and `routes/console.php`.
