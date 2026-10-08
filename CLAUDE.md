# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**Conexa e-learning** — an internal training/onboarding intranet for a single company ("Conexa"), built in Laravel. Scope: one company only, deployed to Hostinger, built in one week (started 2026-10-06). See `doc/arquitectura.md` (Spanish) for the full target architecture, business rules, and rationale — read it before making domain-level decisions; it is the source of truth for intended behavior, not just history.

**Goal:** onboard new workers by organizing induction content into **areas** (= courses) → **modules** → **lessons**, with a quiz gating each module, per-worker progress tracking, a downloadable PDF report, and an optional Duolingo-style learning path on top of the same progress engine.

**Content hierarchy** (fixed depth, free breadth — an area/module can have any number of children):

```
Area 1──N Modulo 1──N Leccion
                 └─1 Quiz 1──N Pregunta 1──N Opcion
```

See the Domain model section below for the full picture including the User-facing relationships (assigned areas, lesson progress, frozen module approvals, feedback).

**7-phase delivery plan** (one phase per day, per `doc/arquitectura.md`'s "Plan de 7 días"; see Current State below for what's actually done so far):

1. **Base** — Laravel project, Breeze without registration, migrations (incl. `modulo_aprobados`), seeders, roles and policies. Trial deploy to Hostinger.
2. **Admin** — Filament panel: nested CRUD area → module → lesson, duplicate, reorder, cargo→area auto-assignment, CSV import.
3. **Worker** — list-mode view: home, lesson viewer, progress recording.
4. **Quizzes** — server-side grading, editable 70% minimum score, attempt history, `modulo_aprobados` recording.
5. **Report** — summary PDF, feedback survey, admin download.
6. **Path + content** — Duolingo-style path mode, visual polish, real content load.
7. **Close** — testing and final deploy.

**Note the codebase is currently in an early stage of this plan** (roughly phase 1 — migrations, models, policies, seeders, auth — see Current State below); don't assume features from later phases (Filament admin, Services layer, quiz-taking UI, PDF reports, the Duolingo path) already exist without checking.

## Commands

Run these from the project root inside WSL (PHP/Composer live there, not in the Windows PATH):

```sh
wsl.exe -e bash -lc "cd /home/jhamil/trabajo/conexa/conexa-elearning && <command>"
```

- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Dev servers (serve + queue + vite, concurrently): `composer run dev`
- Build frontend assets: `npm run build`
- Run all tests: `composer run test` (clears config cache first, then `php artisan test`)
- Run a single test file: `php artisan test tests/Feature/Auth/AuthenticationTest.php`
- Run tests matching a name: `php artisan test --filter=testName`
- Format PHP (after editing any PHP file): `vendor/bin/pint --dirty --format agent`
- Run a migration: `php artisan migrate`
- Artisan help: `php artisan list`, `php artisan <command> --help`

## Architecture

### Domain model (content hierarchy)

Fixed 3-level hierarchy, implemented as Eloquent models in `app/Models`:

```
Area 1──N Modulo 1──N Leccion
                 └─1 Quiz 1──N Pregunta 1──N Opcion
User N──M Area        (areas assigned to a worker)
User N──M Leccion     (pivot: completada_en — lesson viewed/completed)
User 1──N ModuloAprobado ─► Modulo   (frozen pass date + score)
User 1──N Feedback ─► Area
```

- `Area` = a course (e.g. "Ventas", "Seguridad industrial"). Has `modulos()`, `usuarios()` (assigned workers).
- `Modulo` belongs to an `Area`, has ordered `lecciones()` and one `quiz()`.
- `Leccion` belongs to a `Modulo`; content type (`tipo`), rich content, or an external resource URL.
- `Quiz` belongs to a `Modulo`, has ordered `preguntas()`; `nota_minima` is per-quiz (global default is overridable).
- `Pregunta` belongs to a `Quiz`, has `opciones()`.
- `Opcion` belongs to a `Pregunta`; `es_correcta` marks the right answer — **grading must stay server-side**, correct answers must never be sent to the browser.
- `ModuloAprobado` is a frozen snapshot (score + date) written once when a user passes a module — history/PDF reports read from here, not from live module state, so editing a module later never rewrites the past.
- `Feedback` belongs to `feedbacks` table (note the non-default table name on the model) — per-area survey from a user.

Business rules for progress %, module completion, and unlock order are meant to live in a `Services` layer (`ProgresoService`, `QuizService` per the architecture doc) so the dashboard, PDF, and admin panel can't disagree — that layer does not exist yet; when adding this logic, put it there rather than in controllers/models.

### Roles & access control

- Two roles only, stored as `users.rol` (string: `admin` | `trabajador`), checked via `User::esAdmin()`. No role package (Spatie, etc.) — intentionally, since there are only two roles.
- `App\Http\Middleware\EnsureAdmin` (aliased `admin`) gates admin-only routes by calling `esAdmin()`.
- Authorization to a specific Area/Modulo/Leccion record is done via Policies (`AreaPolicy` exists; `ModuloPolicy`/`LeccionPolicy` are planned but not yet created) — always check assignment (`$user->areas()`), not just role, before showing content tied to a specific record.
- Public registration is intentionally disabled (routes commented out in `routes/auth.php`) — only an admin creates accounts, workers get a temporary password and must change it on first login (`users.debe_cambiar_password`).
- Post-login redirect branches by role in `AuthenticatedSessionController::store()`: `/admin` for admins, `/dashboard` for workers.

### Current state vs. target architecture

Implemented: migrations for all domain tables, Eloquent models + relationships, `AreaPolicy`, `EnsureAdmin` middleware, `AdminSeeder`/`AreaSeeder`, standard Laravel Breeze auth (Blade + Alpine.js + Tailwind), registration disabled.

Not yet implemented (per `doc/arquitectura.md`'s plan — check before assuming they exist): Filament admin panel, `app/Services` layer, worker-facing controllers/views for area/lesson/quiz/report flows, `ModuloPolicy`/`LeccionPolicy`, PDF report generation (`barryvdh/laravel-dompdf`), HTML sanitization (`mews/purifier`), the Duolingo-style path UI.

### Stack

Laravel 13, PHP 8.3+, MySQL/MariaDB (`DB_CONNECTION=mysql` in `.env`, not SQLite despite what `phpunit.xml` uses for tests), Breeze (Blade + Tailwind + Alpine.js), Vite. Target deployment is Hostinger shared hosting (see deployment steps in `doc/arquitectura.md` if working on deploy config).

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
