# Agent Notes

## Stack and entrypoints

- This is a Laravel 12 + Inertia + Vue 3 app (not a monorepo).
- Backend HTTP routes: `routes/web.php` and `routes/api.php`.
- Frontend boot file: `resources/js/app.ts`; Vite entry is `resources/js/app.ts` (`vite.config.ts`).
- Client-side feed routing is in `resources/js/router/index.ts` and maps `/index` to `resources/js/pages/NewsFeed.vue`.

## Commands that match repo/CI

- Initial setup: `composer run setup` (installs deps, creates `.env`, generates key, migrates, builds assets).
- Local full dev loop: `composer run dev` (Laravel server + queue worker + pail logs + Vite via `concurrently`).
- PHP tests: `composer test` (clears config, then `php artisan test`).
- Single test: `php artisan test --filter=PostIndexTest`.
- Frontend quality commands used by CI lint workflow: `vendor/bin/pint`, `npm run format`, then `npm run lint`.

## Important repo-specific gotchas

- `routes/api.php` wraps `/api/posts` in `Route::middleware('auth:api')`; unauthenticated requests will fail unless guard config is changed.
- `routes/web.php` also exposes `GET /apis/posts` (`PostController::legacyIndex`) with a legacy response shape; do not remove casually.
- ESLint ignores generated UI primitives in `resources/js/components/ui/*`; Prettier also ignores that path (`.prettierignore`).
- TS path alias `@/*` maps to `resources/js/*` (`tsconfig.json`).

## Style/tooling constraints to preserve

- Formatting defaults are 4-space indentation (`.editorconfig` + `.prettierrc`), but YAML is 2 spaces.
- Prettier plugins auto-organize imports and sort Tailwind classes; run formatter after significant Vue/TS edits.
