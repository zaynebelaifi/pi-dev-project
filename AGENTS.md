# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project Snapshot

- Stack: Symfony 6.4 + PHP 8.1+, Doctrine ORM/Migrations, Twig, Webpack Encore.
- App style: Monolith with domain folders (orders, delivery, reservation, dish, donation, user).
- Entry route loading uses controller attributes (not route annotations in YAML files).

Key references:
- [composer.json](composer.json)
- [package.json](package.json)
- [config/routes.yaml](config/routes.yaml)
- [config/packages/doctrine_migrations.yaml](config/packages/doctrine_migrations.yaml)
- [config/services.yaml](config/services.yaml)
- [config/packages/security.yaml](config/packages/security.yaml)

## High-Value Commands

- Install backend deps: `composer install`
- Run Symfony console: `php bin/console <command>`
- Run tests: `php bin/phpunit`
- Frontend dev build: `npm run dev`
- Frontend watch: `npm run watch`
- Frontend production build: `npm run build`
- Apply DB migrations: `php bin/console doctrine:migrations:migrate`

Notes:
- Composer auto-scripts run after install/update (`cache:clear`, `assets:install`, `importmap:install`).
- PHPUnit test environment is configured via [phpunit.dist.xml](phpunit.dist.xml).

## Where To Work

Primary editable areas:
- `src/` for PHP logic (Controller, Entity, Repository, Service, Form, Command, EventListener)
- `templates/` for Twig views
- `config/` for framework/bundle configuration
- `migrations/` for schema changes
- `tests/` for PHPUnit tests
- `assets/` for frontend source assets

Avoid editing generated or vendor-managed paths unless explicitly requested:
- `var/`
- `vendor/`
- `public/assets/`
- `assets/vendor/`

## Important Pitfalls

- There is a nested duplicate project at `pi-dev-project/` inside the repository root. Treat it as legacy/mirror content unless the task explicitly targets it.
- Security config currently uses an in-memory provider in [config/packages/security.yaml](config/packages/security.yaml). Validate auth assumptions before changing protected flows.
- Database fields may use snake_case names while PHP APIs use camelCase accessors. Keep existing patterns in each entity.

## Coding Conventions In Practice

- Use PSR-4 namespace layout under `App\\` from `src/`.
- Prefer constructor dependency injection for controllers/services/commands.
- Keep service classes `final` when possible and add strict typing.
- Controllers generally use `#[Route(...)]` attributes and render Twig templates.
- Forms are defined as Symfony `AbstractType` classes in `src/Form/`.

## Change Workflow For Agents

1. Read target domain entity/repository/controller before editing.
2. Make minimal scoped edits in the root project (not nested duplicate directory).
3. For schema changes, add/update migration in `migrations/` and run migrate locally.
4. Run focused validation:
   - `php bin/phpunit` for backend changes
   - `npm run build` (or `npm run dev`) for frontend asset changes
5. If config changes affect security/routing/messenger, verify related config in `config/packages/` and `config/routes.yaml`.

## Environment And Secrets

- Local overrides belong in `.env.local` (ignored by git).
- Do not commit secrets or generated cache/build artifacts.
- Respect ignore rules in [.gitignore](.gitignore).
