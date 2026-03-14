# Repository Guidelines

## Project Context
This repository is a multi-module electoral control system focused on vote registration, mesa-level results, party totals, and synchronization between backend and frontend views.

The operational priority is electoral correctness. When code affects totals, summaries, filters, or validations, preserve consistency across:
- `partidos`
- `mesas`
- `resultados_mesa`
- `detalles`
- `votos`

The current political focus is **Jacha**, which corresponds to **party 15** in `partidos`. If party-specific defaults, labels, filters, or validations are needed and the code does not clearly require generic multi-party behavior, use Jacha (`partido_id = 15`) as the project reference.

## Critical Rule: No Deployment
Do not deploy anything.
Do not run production deployment commands.
Do not modify infrastructure or hosting configuration unless the user explicitly requests it.

Not allowed by default:
- production deploys
- server changes
- nginx, apache, docker, pm2, supervisor, ssl, dns, ci/cd, or hosting edits
- destructive server commands

Allowed scope:
- `back/` Laravel backend work
- `front/` Quasar/Vue frontend work
- API contract adjustments between backend and frontend
- migrations, models, controllers, routes, pages, and components
- validations, queries, calculations, and UI improvements

## Preferred Scope
Prioritize work in:
- `back/`
- `front/`

Avoid changes in:
- `app/`
- `socket/`

Only touch those modules when the task explicitly requires it.

## Project Structure
- `app/`: Flutter mobile app (`lib/`, `assets/`)
- `back/`: Laravel API (`app/Http/Controllers`, `app/Models`, `routes/api.php`, `database/migrations`, `tests/`)
- `front/`: Quasar/Vue admin frontend (`src/pages`, `src/components`, `src/router`)
- `socket/`: Node.js Socket.IO service (`index.js`)

Keep changes scoped to one module when possible. If backend response shapes or API contracts change, update the consuming frontend in the same task.

## Domain Rules
When modifying electoral logic:

1. Never assume totals without verifying detail rows.
2. Mesa totals must match the sum of their stored vote details.
3. Party totals must be derived consistently from stored votes.
4. Preserve consistency between summary rows and detail rows.
5. Prefer auditable and deterministic calculations over implicit shortcuts.
6. Do not hardcode fragile values unless the requirement explicitly asks for it.
7. Preserve traceability so reviewers can understand how totals were calculated.
8. If there is tension between UI convenience and electoral correctness, choose correctness.

## Data Integrity Priorities
Any change affecting `partidos`, `mesas`, `resultados_mesa`, `detalles`, or `votos` must preserve:
- data integrity
- correct aggregation by mesa
- correct aggregation by party
- consistency between detail and summary records
- stable API output for frontend consumers

Avoid destructive schema changes unless explicitly requested. Prefer additive migrations with clear intent.

## Backend Guidelines
Use Laravel in `back/` for:
- business rules
- validations
- electoral result calculations
- relational consistency
- secure API endpoints
- query/report preparation for frontend consumption

Backend conventions:
- follow PSR-12
- controllers end with `Controller`
- models are singular, for example `Partido`, `Mesa`, `ResultadoMesa`, `Voto`, `Detalle`
- keep controllers thin when practical
- move reusable complex logic into services/actions when complexity grows

Backend priorities:
1. correctness
2. validation
3. relational consistency
4. readable queries
5. API compatibility

Validate carefully on create/update/sync endpoints, especially for:
- mesa IDs
- partido IDs
- vote counts
- detail payloads
- result summaries

## Frontend Guidelines
Use Quasar/Vue in `front/` for:
- result tables
- party summaries
- mesa listings
- vote detail views
- filters
- dashboards
- operator workflows

Frontend conventions:
- use `PascalCase.vue`
- keep components readable and maintainable
- follow the existing project UI patterns unless the user asks for redesign
- do not hardcode data that should come from the backend
- when displaying totals, reflect backend truth

Frontend priorities:
1. accuracy of displayed results
2. clarity of party and mesa information
3. consistent labels and filters
4. safe editing flows
5. simple, reliable operator-facing UI

If UI and data disagree, fix the data flow instead of masking the inconsistency in the interface.

## Build, Test, and Development Commands
Backend:
- `cd back && composer install`
- `cd back && php artisan serve`
- `cd back && php artisan test`
- `cd back && composer run dev`

Frontend:
- `cd front && npm install`
- `cd front && npm run dev`
- `cd front && npm run build`

Flutter:
- `cd app && flutter pub get`
- `cd app && flutter run`
- `cd app && flutter analyze`

Socket:
- `cd socket && npm install`
- `cd socket && node index.js`

## Testing Guidelines
Backend tests use Pest:
- `back/tests/Feature`
- `back/tests/Unit`

Add or update tests when changing:
- electoral result calculations
- mesa validations
- vote registration
- party aggregation
- synchronization behavior
- permissions or auth around result editing

Test names should describe behavior, for example:
- `it_calculates_resultado_mesa_from_votos`
- `it_blocks_invalid_votes_for_mesa`
- `it_filters_results_for_jacha_party`
- `it_keeps_party_totals_consistent_with_detail_rows`

Before considering a backend task finished, run:
- `cd back && php artisan test`

## Coding Style
Dart / Flutter:
- follow `flutter_lints`
- filenames in `snake_case.dart`
- classes/widgets in `PascalCase`

PHP / Laravel:
- PSR-12
- descriptive method names
- avoid unnecessary abbreviations

Vue / Quasar:
- `PascalCase.vue`
- clear prop and method names
- keep methods focused

General:
- prefer descriptive names over abbreviations
- keep functions short and focused
- avoid unexplained magic numbers
- if using party-specific constants, document them clearly

## Commits and Pull Requests
Prefer clear commit messages such as:
- `back: valida consistencia entre votos y resultados mesa`
- `front: mejora filtro de partidos y mesas`
- `back: agrega soporte para Jacha partido 15 en resumen`
- `front: corrige visualizacion de detalle de votos por mesa`

PRs should include:
- what changed
- why it changed
- affected modules
- manual test steps
- expected result
- screenshots or videos for UI changes

If electoral totals are affected, explicitly document:
- which totals changed
- how correctness was verified
- whether `resultados_mesa`, `detalles`, and `votos` remain consistent

## Security and Configuration
- never commit real secrets
- use `.env` files per module
- validate auth/authorization on protected endpoints
- avoid hardcoded credentials or test users
- do not change production configuration unless explicitly requested

## Agent Working Rules
When working in this repository:

1. First determine whether the task belongs to `back/` or `front/`.
2. Prefer minimal, targeted changes.
3. Do not refactor unrelated areas.
4. If backend contracts change, update frontend consumers in the same task.
5. Preserve electoral correctness above all else.
6. Do not deploy.
7. Do not touch infrastructure unless explicitly requested.
8. Verify carefully any relationship involving `resultados_mesa`, `partidos`, `mesas`, `detalles`, or `votos`.
9. Use Jacha (`partido_id = 15`) as the main contextual reference when party-specific behavior is needed.
10. Produce code that is easy to review, audit, and deploy manually by the user.

## Summary
This is an electoral control project where correctness of party and mesa data is critical. The main coding scope is Laravel in `back/` and Quasar/Vue in `front/`. Preserve consistency across `resultados_mesa`, `detalles`, and `votos`, keep backend/frontend behavior aligned, and avoid any deployment or infrastructure work unless explicitly requested.
