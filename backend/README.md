# Shipment API — Laravel 13 + Sanctum + MySQL

A small courier/logistics API. A customer books a parcel, the system calculates the price, books it with a courier company in the background, and keeps a full status history. An admin moves the parcel through its statuses.

The project is intentionally small: **one feature, built properly**. It shows authentication, authorization (policies), all three kinds of middleware, validation, a custom service provider with dependency injection, queues/jobs, an observer, events and listeners, and a clean JSON API ready for an Angular front end.

> For a file-by-file explanation and interview talking points, read **[docs/INTERVIEW_GUIDE.md](docs/INTERVIEW_GUIDE.md)**.

---

## 1. Requirements

- PHP 8.3+
- Composer 2
- MySQL 8 (or MariaDB 10.6+)
- Node is **not** needed (this is an API only)

## 2. Setup

```bash
# 1. Install PHP packages
composer install

# 2. Create your .env and app key
cp .env.example .env
php artisan key:generate

# 3. Create the database in MySQL
mysql -u root -p -e "CREATE DATABASE shipment_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
#    then check DB_USERNAME / DB_PASSWORD in .env

# 4. Create tables and demo data
php artisan migrate --seed
```

Demo users (password for both: `password`):

| Role     | Email                  |
|----------|------------------------|
| Admin    | admin@example.com      |
| Customer | customer@example.com   |

## 3. Run it

You need **two terminals**: one for the web server, one for the queue worker.

```bash
# Terminal 1 — the API on http://localhost:8000
php artisan serve

# Terminal 2 — the queue worker (handles courier booking + emails)
php artisan queue:work --queue=courier,notifications,default
```

Emails use `MAIL_MAILER=log`, so you can read them in `storage/logs/laravel.log`.

To see **retries** in action, set `COURIER_FAIL_RATE=0.5` in `.env`, restart the worker and create a few shipments.

## 4. Run the tests

Tests use SQLite in memory (see `phpunit.xml`), so they don't touch your MySQL data.

```bash
php artisan test
```

---

## 5. API reference

Base URL: `http://localhost:8000/api`
Send `Authorization: Bearer <token>` on every route except register and login.
All responses are JSON. Every response has an `X-Request-Id` header.

### Auth

| Method | URL               | Body                                                | Notes |
|--------|-------------------|-----------------------------------------------------|-------|
| POST   | `/auth/register`  | `name, email, password, password_confirmation`      | Returns `user` + `token`. Max 10/min. |
| POST   | `/auth/login`     | `email, password, device_name?`                     | Returns `user` + `token`. Max 5/min. |
| GET    | `/auth/me`        | –                                                   | Current user. |
| POST   | `/auth/logout`    | –                                                   | Deletes the current token. Returns 204. |

Login response:

```json
{
  "user": { "id": 2, "name": "Ali Customer", "email": "customer@example.com", "role": "customer" },
  "token": "3|Qm9vZ3Vz..."
}
```

### Shipments

| Method | URL                             | Who            | Notes |
|--------|---------------------------------|----------------|-------|
| GET    | `/dashboard`                    | logged in      | Counts per status. Customers see only their own. |
| POST   | `/shipments/quote`              | logged in      | Price only, nothing saved. For the live price on the form. |
| GET    | `/shipments`                    | logged in      | Paginated. Filters: `status`, `search`, `per_page`. |
| POST   | `/shipments`                    | logged in      | Creates a shipment and queues the courier booking. 201. |
| GET    | `/shipments/{id}`               | owner or admin | Includes `history`. |
| POST   | `/shipments/{id}/cancel`        | owner or admin | Only `pending` or `booked`. |
| PATCH  | `/admin/shipments/{id}/status`  | **admin only** | Body: `{ "status": "in_transit" }` |

Create shipment — request:

```json
{
  "sender_name": "Sajid",
  "sender_city": "Lahore",
  "receiver_name": "Ahmed",
  "receiver_phone": "03001234567",
  "receiver_address": "House 1, Street 2, DHA",
  "receiver_city": "Karachi",
  "weight_kg": 2
}
```

Shipment — response:

```json
{
  "data": {
    "id": 12,
    "tracking_number": "SHP261019K4P9ZQ",
    "sender":   { "name": "Sajid", "city": "Lahore" },
    "receiver": { "name": "Ahmed", "phone": "03001234567", "address": "House 1, Street 2, DHA", "city": "Karachi" },
    "weight_kg": 2.0,
    "price": 750.0,
    "status": { "value": "pending", "label": "Pending" },
    "courier_reference": null,
    "booked_at": null,
    "created_at": "2026-10-19T06:00:00+00:00",
    "can": { "cancel": true, "update_status": false },
    "history": [
      { "from": null, "to": "pending", "to_label": "Pending", "changed_by": "Ali Customer", "at": "2026-10-19T06:00:00+00:00" }
    ]
  }
}
```

The `can` block tells the Angular app which buttons to show, so the front end never repeats the permission rules.

Quote — response:

```json
{ "data": { "base_fee": 200, "weight_charge": 300, "same_city": false, "total": 750 } }
```

Dashboard — response:

```json
{ "data": { "total": 11, "by_status": { "pending": 3, "booked": 2, "in_transit": 2, "delivered": 3, "cancelled": 1 } } }
```

List — response: `data` (array of shipments), plus Laravel's `links` and `meta` (`current_page`, `last_page`, `per_page`, `total`).

### Status rules

```
pending ---> booked ---> in_transit ---> delivered
   |            |
   +------------+---> cancelled
```

An invalid move (for example `pending -> delivered`) returns **422** with a clear message.

### Error format

| Code | When | Example body |
|------|------|--------------|
| 401 | Missing or wrong token | `{ "message": "Unauthenticated." }` |
| 403 | Not allowed (policy or role) | `{ "message": "Only pending or booked shipments can be cancelled." }` |
| 404 | Shipment not found | `{ "message": "..." }` |
| 422 | Validation failed | `{ "message": "...", "errors": { "receiver_phone": ["Enter a valid mobile number like 03001234567."] } }` |
| 429 | Too many login attempts | `{ "message": "Too Many Attempts." }` |

---

## 6. Using it from Angular

- CORS allows `FRONTEND_URL` (default `http://localhost:4200`). Change it in `.env`.
- Save the `token` after login, and add it to every request with an **HTTP interceptor**: `Authorization: Bearer <token>`.
- On a **401**, the interceptor should clear the token and send the user to `/login`.
- On a **422**, read `error.error.errors` and show each message under the matching form field.
- Suggested screens: Login, Dashboard, Shipments list with filters, New shipment form with live price, Shipment detail with history and a Cancel button (`can.cancel`), and an admin status dropdown (`can.update_status`).

Example TypeScript types:

```ts
export type ShipmentStatus = 'pending' | 'booked' | 'in_transit' | 'delivered' | 'cancelled';

export interface Shipment {
  id: number;
  tracking_number: string;
  sender: { name: string; city: string };
  receiver: { name: string; phone: string; address: string; city: string };
  weight_kg: number;
  price: number;
  status: { value: ShipmentStatus; label: string };
  courier_reference: string | null;
  booked_at: string | null;
  created_at: string;
  can: { cancel: boolean; update_status: boolean };
  history?: { from: ShipmentStatus | null; to: ShipmentStatus; to_label: string; changed_by: string; at: string }[];
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}
```

---

## 7. Project map

```
app/
  Contracts/CourierGateway.php              interface (abstraction)
  Enums/ShipmentStatus.php, UserRole.php    statuses + transition rules
  Events/ShipmentStatusChanged.php
  Exceptions/CourierUnavailableException.php
  Http/
    Controllers/Api/                        AuthController, ShipmentController, DashboardController
    Controllers/Api/Admin/                  ShipmentStatusController (invokable)
    Middleware/                             AssignRequestId (global), ForceJsonResponse (group), EnsureUserHasRole (route)
    Requests/                               Form Request validation
    Resources/                              JSON output shape
  Jobs/BookShipmentWithCourier.php          queued job with retries
  Listeners/                                RecordStatusHistory (sync), SendStatusNotification (queued)
  Models/                                   User, Shipment, ShipmentStatusHistory
  Notifications/ShipmentStatusUpdated.php
  Observers/ShipmentObserver.php
  Policies/ShipmentPolicy.php
  Providers/ShipmentServiceProvider.php     container bindings, observer, policy, events
  Services/                                 ShipmentService, RateCalculator, Courier/{Fake,Http}CourierGateway
bootstrap/app.php                           middleware setup, api routes, event discovery off
config/shipping.php                         prices + courier driver
routes/api.php
tests/                                      Feature + Unit tests
```

## 8. Switching to Redis + Horizon (production)

The project uses the `database` queue so it runs with no extra setup. For production:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Then set `QUEUE_CONNECTION=redis`, add supervisors for the `courier` and `notifications` queues in `config/horizon.php`, run `php artisan horizon` instead of `queue:work`, and run `php artisan horizon:terminate` after every deploy.
