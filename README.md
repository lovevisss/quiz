# Facebook Clone Feed API

This project now includes a database-backed posts feed loaded in the frontend with Axios.

## What was added

- Laravel `posts` table migration.
- `Post` model + factory + API controller.
- `/api/posts` endpoint in `routes/api.php`.
- Vue Router `/index` route now renders `NewsFeed.vue`.
- `NewsFeed.vue` fetches posts from `/api/posts` using Axios.
- Feature test: `tests/Feature/Api/PostIndexTest.php`.

## Quick start

1. Install dependencies.
2. Run migrations.
3. Seed data.
4. Start Laravel + Vite.

```powershell
composer install
npm install
php artisan migrate
php artisan db:seed
php artisan serve
npm run dev
```

Open `http://127.0.0.1:8000/index`.

## Verify

```powershell
php artisan test --filter=PostIndexTest
php artisan route:list | Select-String -Pattern "api/posts"
```

