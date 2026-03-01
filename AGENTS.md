# Repository Guidelines

## Project Structure & Module Organization
This repository is a multi-module workspace:
- `app/`: Flutter mobile app (`lib/` views, services, models; `assets/` images/icons).
- `back/`: Laravel API (`app/Http/Controllers`, `app/Models`, `routes/api.php`, `database/migrations`, `tests/`).
- `front/`: Quasar/Vue admin frontend (`src/pages`, `src/components`, `src/router`).
- `socket/`: Node.js Socket.IO service (`index.js`).

Keep changes scoped to one module when possible. If you update API contracts, update Flutter/Quasar consumers in the same PR.

## Build, Test, and Development Commands
- Backend:
  - `cd back && composer install`
  - `cd back && php artisan serve` (API local)
  - `cd back && php artisan test` (Pest tests)
  - `cd back && composer run dev` (serve + queue + vite)
- Flutter:
  - `cd app && flutter pub get`
  - `cd app && flutter run`
  - `cd app && flutter analyze`
- Frontend (Quasar):
  - `cd front && npm install`
  - `cd front && npm run dev`
  - `cd front && npm run build`
- Socket:
  - `cd socket && npm install`
  - `cd socket && node index.js`

## Coding Style & Naming Conventions
- Dart/Flutter: follow `flutter_lints`; use `snake_case.dart` filenames and clear `PascalCase` widget/class names.
- PHP/Laravel: PSR-12 style; controllers end with `Controller`, models singular (`User`, `Mesa`).
- Vue/Quasar: component/page files use `PascalCase.vue`.
- Prefer descriptive names over abbreviations; keep functions short and focused.

## Testing Guidelines
- Backend uses Pest (`back/tests/Feature`, `back/tests/Unit`).
- Add/adjust tests for every behavior change in auth, permissions, and result sync flows.
- Name tests by behavior, e.g. `it_blocks_sync_for_unassigned_mesa`.
- Run `php artisan test` before opening a PR.

## Commit & Pull Request Guidelines
Current history uses short Spanish messages (e.g., `Actulizado...`, `recinto mejorado`) without a strict convention.  
Use a clearer pattern going forward:
- `back: valida mesa en sync mobile`
- `app: tabs alcalde/concejal y bloqueo post-envio`

PRs should include:
- What changed and why.
- Affected modules (`app/back/front/socket`).
- Manual test steps and expected result.
- Screenshots/videos for UI changes.

## Security & Configuration Tips
- Never commit real secrets; use `.env` files per module.
- Validate auth/authorization on every backend endpoint (especially mobile sync paths).
- Avoid hardcoded credentials or test users in UI code.
