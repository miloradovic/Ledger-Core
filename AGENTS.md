# Agent Instructions (Laravel 12 / PHP 8.4 / PHPUnit 12)

## Project Basics
- Framework: Laravel 12
- Language: PHP 8.4
- Use Composer for dependencies; do not modify `composer.lock` unless dependencies change.

## Local Setup
- Copy `.env.example` to `.env` and set required values.
- Run:
  - `composer install`
  - `php artisan key:generate`
  - `php artisan migrate` (only if needed for the task)

## Coding Standards
- Follow PSR-12 and Laravel conventions.
- Prefer Laravel helpers/facades over custom utilities unless required.
- Use dependency injection and strict typing where possible.

## Testing
- App is running inside Docker container `ledger-core-app`.
- PHPUnit 12:
  - `php artisan test`
  - or `./vendor/bin/phpunit`
- If tests are not run, explain why in the summary.

## Database & Migrations
- Keep migrations reversible.
- Avoid destructive changes unless explicitly required.

## Static Analysis / Quality
- If configured, run:
  - `./vendor/bin/phpstan` or `./vendor/bin/larastan`
  - `./vendor/bin/phpcs`
- If not run, explain why.

## PR Expectations
- Provide a concise summary.
- List tests run (or note if not run).
- Call out any follow-ups or risks.

## Safety
- Do not commit secrets, `.env`, or local artifacts.

## Project Philosophy (Lean Laravel)
- Keep the codebase small, idiomatic, and Laravel-native.
- Prefer built-in Laravel features over custom abstractions.
- Avoid premature optimization and unnecessary layers.
- Favor readability, conventions, and framework defaults.

## Laravel Way Principles
- Use Eloquent for data access; avoid raw SQL unless necessary.
- Prefer Form Requests for validation and Policies for authorization.
- Use Jobs, Events, and Notifications when behavior crosses boundaries.
- Keep controllers thin; push logic into models, actions, or services only when needed.
- Use Artisan generators and adhere to Laravel folder conventions.
- Avoid over-engineering: no repositories, DTOs, or service layers unless justified.

## Dependency Discipline
- Add packages only when the framework cannot reasonably solve it.
- Prefer official or well-maintained Laravel ecosystem packages.