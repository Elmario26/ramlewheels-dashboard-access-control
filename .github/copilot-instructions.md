# RamleWheels - AI Agent Instructions

## Project Overview
RamleWheels is a car inventory management system built with Symfony 7.3 and uses a modern frontend stack including Tailwind CSS, Turbo, and Alpine.js. The system manages car listings with details like brand, year, mileage, condition, and price.

## Architecture & Key Components

### Backend (Symfony)
- Controllers in `src/Controller/` follow a resource-based structure (e.g., `CarsController.php`)
- Entities in `src/Entity/` with Doctrine ORM annotations using PHP 8 attributes
- Form types in `src/Form/` following Symfony form patterns
- Repository classes in `src/Repository/` for database operations
- RESTful + HTML hybrid API support (check Accept headers for JSON/HTML responses)

### Frontend
- Base template: `templates/base.html.twig` with fixed sidebar layout
- Component-based structure using Twig templates in `templates/main/` and `templates/partials/`
- Asset compilation managed by Webpack Encore (`webpack.config.js`)
- AlpineJS used for client-side interactivity (loaded via CDN in `base.html.twig`)
## RamleWheels — AI agent quick instructions

This file tells AI coding agents how the project is organized and which patterns and commands are important to be productive quickly.

High level
- Symfony 7.3 backend (PHP >= 8.2) with Doctrine ORM for persistence.
- Frontend: Webpack Encore → PostCSS/Tailwind, Stimulus controllers and Turbo for SPA-like navigation. Assets live in `assets/` and compile to `public/build/`.

What to read first (entry points)
- `src/Controller/` — controller patterns (resource-based controllers, e.g. `CarsController`).
- `src/Entity/` and `src/Repository/` — data model and DB access.
- `assets/app.js`, `assets/bootstrap.js`, `assets/controllers.json` — frontend entry + Stimulus wiring.
- `templates/base.html.twig` and `templates/main/` — layout and partials.
- `webpack.config.js`, `package.json`, `composer.json` — build/runtime dependencies and npm scripts.

Practical developer commands (used by this repo)
- Start PHP/Symfony dev server: `symfony serve` (recommended)
- Run DB + phpMyAdmin (docker-compose): `docker-compose up -d` (see `docker-compose.yaml`)
- Doctrine migrations: `php bin/console doctrine:migrations:migrate` and `php bin/console doctrine:migrations:diff`
- Frontend watch/build: `npm run watch` (dev) and `npm run build` (production). Entrypoints are defined in `webpack.config.js`.

Project-specific conventions
- Twig partials use leading underscores (e.g. `_sidebar.html.twig`).
- Stimulus controllers live in `assets/controllers/` and are referenced by `assets/controllers.json` (see `assets/controllers.json` for enabled controllers).
- Webpack Encore configuration: uses `enableStimulusBridge('./assets/controllers.json')` and outputs to `public/build/`.

Patterns and examples
- Controller + form flow (common): createForm → handleRequest → if submitted & valid then persist + flush. See `src/Controller/CarsController.php` and `src/Form/CarsType.php`.
- Entities use typed properties and PHP 8 attributes for mappings (check `src/Entity/*`).
- Templates use block inheritance; partials live under `templates/main/partials`.

Integration points
- Database: MySQL via Docker (credentials in repo README / docker-compose). Migrations are the source of truth in `migrations/`.
- Frontend: Stimulus + Turbo; builds are invoked via npm scripts (see `package.json`).
- Optional features: Mercure/Turbo-stream support toggled in `assets/controllers.json`.

If you modify or generate files
- After changing assets, run `npm run watch` or `npm run build` to update `public/build/`.
- After changing entities, run `php bin/console make:migration` (or `doctrine:migrations:diff`) and then `doctrine:migrations:migrate`.

Where not to guess
- Do not change database credentials or migration files without running migrations locally and verifying with phpMyAdmin.

Files worth scanning for context
- `composer.json`, `package.json`, `webpack.config.js`
- `src/Controller/`, `src/Entity/`, `src/Form/`, `src/Repository/`
- `assets/` and `templates/`

Feedback
- If anything here is unclear or a workflow is missing (CI, special docker steps), tell me which part and I'll update this file.