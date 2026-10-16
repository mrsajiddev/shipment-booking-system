# Shipment Booking System

A small courier booking system: a **Laravel 13 REST API** and an **Angular 21** front end.

Customers book parcels with a live price, a queued job books them with a courier,
and every status change is tracked. Admins move shipments through their statuses.

| Folder | What | Docs |
|--------|------|------|
| `backend/`  | Laravel 13, Sanctum, MySQL, queues, policies, events | [backend/README.md](backend/README.md) |
| `frontend/` | Angular 21, signals, RxJS, reactive forms, interceptors | [frontend/README.md](frontend/README.md) |

## Quick start
1. `cd backend && composer install && cp .env.example .env && php artisan key:generate && php artisan migrate --seed`
2. `php artisan serve` and, in a second terminal, `php artisan queue:work --queue=courier,notifications,default`
3. `cd frontend && npm ci && npm start`, then open http://localhost:4200

Demo logins: customer@example.com / admin@example.com, password `password`.

![Dashboard](docs/screenshots/02-dashboard.png)
