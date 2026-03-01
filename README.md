# GSI Pocket API

A RESTful API for personal pocket / wallet management built with **Laravel 12**, **PHP 8.3**, and **PostgreSQL 16**, containerised with Docker.

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Reproducibility](#reproducibility)
- [Testing](#testing)
- [API Reference](#api-reference)

---

## Prerequisites

Make sure the following are installed on your machine before running the project.

| Requirement | Minimum Version |
|-------------|-----------------|
| [Docker](https://docs.docker.com/get-docker/) | 24.x |
| [Docker Compose](https://docs.docker.com/compose/install/) | 2.x |
| Git | any |

> **Note:** PHP and Composer do **not** need to be installed locally — they run inside Docker containers.

---

## Reproducibility

### 1. Clone the repository

```bash
git clone https://github.com/ismyusuf/gsi-pocket-api.git
cd gsi-pocket-api
```

### 2. Configure the environment

Copy the example environment file and adjust values as needed:

```bash
cp src/.env.example src/.env
```

Then open `src/.env` and set the following database variables to match the Docker Compose configuration:

```dotenv
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

QUEUE_CONNECTION=database

JWT_SECRET=   # will be generated in step 4
```

### 3. Build and start containers

```bash
docker compose up -d --build
```

This starts four services:

| Container | Role | Port |
|---|---|---|
| `laravel_app` | PHP-FPM application | — |
| `laravel_nginx` | Nginx web server | **8000** |
| `laravel_postgres` | PostgreSQL database | 5432 |
| `laravel_queue` | Laravel queue worker | — |

### 4. Generate application keys

```bash
# Application key
docker compose exec app php artisan key:generate

# JWT secret
docker compose exec app php artisan jwt:secret
```

### 5. Run migrations and seeders

```bash
docker compose exec app php artisan migrate --seed
```

This creates all tables and seeds two test users:

| Full Name | Email | Password |
|-----------|-------|----------|
| User 1 | example1@mail.net | password |
| User 2 | example2@mail.net | password |

### 6. Verify the installation

```bash
curl http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"example1@mail.net","password":"password"}'
```

A successful response returns a `200` status with a JWT token.

### Stopping the application

```bash
docker compose down
```

To also remove the database volume:

```bash
docker compose down -v
```

---

## Testing

The test suite uses **PHPUnit** with an in-memory **SQLite** database — no separate test database setup required.

### Run all tests

```bash
docker exec laravel_app php artisan test
```

### Run only the API tests

```bash
docker exec laravel_app php artisan test --filter ApiTest
```

### Run with verbose output

```bash
docker exec laravel_app php artisan test --filter ApiTest --verbose
```

### Expected output

```
   PASS  Tests\Feature\ApiTest
  ✓ user can login with valid credentials
  ✓ login fails with wrong password
  ✓ login validates required fields
  ✓ user can get own profile
  ✓ profile requires authentication
  ✓ user can create pocket
  ✓ create pocket validates required fields
  ✓ create pocket requires authentication
  ✓ user can list own pockets
  ✓ list pockets does not include other users pockets
  ✓ user can create income and balance increases
  ✓ create income validates required fields
  ✓ create income requires authentication
  ✓ user can create expense and balance decreases
  ✓ create expense validates required fields
  ✓ create expense requires authentication
  ✓ user can get total balance across all pockets
  ✓ total balance is zero when no pockets
  ✓ total balance only counts own pockets
  ✓ user can create report and job is dispatched
  ✓ create report validates type and date
  ✓ create report returns 404 for another users pocket
  ✓ report stream downloads xlsx file
  ✓ report stream returns 404 when file missing
  ✓ full api flow

  Tests:    25 passed (99 assertions)
```

### Test coverage summary

| Endpoint | Tests |
|----------|-------|
| `POST /api/auth/login` | valid credentials, wrong password, validation |
| `GET /api/auth/profile` | returns profile, requires auth |
| `POST /api/pockets` | creates pocket, validation, requires auth |
| `GET /api/pockets` | lists own pockets, isolates other users |
| `POST /api/incomes` | balance increases, validation, requires auth |
| `POST /api/expenses` | balance decreases, validation, requires auth |
| `GET /api/pockets/total-balance` | correct total, zero when empty, isolates users |
| `POST /api/pockets/:id/create-report` | dispatches job, validation, 404 for other user's pocket |
| `GET /reports/:id` | streams file, 404 when missing |
| **Full e2e flow** | complete API flow in README order |

> The test environment is fully self-contained. `JWT_SECRET` and `APP_KEY` are pre-configured in `phpunit.xml` so no `.env` changes are needed to run tests.

---

## API Reference

**Base URL:** `http://localhost:8000/api`

All protected endpoints require the `Authorization` header:

```
Authorization: Bearer <jwt_token>
```

---

### Authentication

#### Login

```
POST /auth/login
```

**Request body:**

```json
{
  "email": "example1@mail.net",
  "password": "password"
}
```

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Berhasil login.",
  "data": {
    "token": "jwt_token"
  }
}
```

---

#### Get User Profile

```
GET /auth/profile
```

🔒 Requires authentication.

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Berhasil.",
  "data": {
    "full_name": "User 1",
    "email": "example1@mail.net"
  }
}
```

---

### Pockets

#### Create Pocket

```
POST /pockets
```

🔒 Requires authentication.

**Request body:**

```json
{
  "name": "Pocket 1",
  "initial_balance": 2000000
}
```

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Berhasil membuat pocket baru.",
  "data": {
    "id": "pocket_id"
  }
}
```

---

#### List Pockets

```
GET /pockets
```

🔒 Requires authentication.

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Berhasil.",
  "data": [
    {
      "id": "pocket_id",
      "name": "Pocket 1",
      "current_balance": 2000000
    }
  ]
}
```

---

#### Get Total Balance

```
GET /pockets/total-balance
```

🔒 Requires authentication.

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Berhasil.",
  "data": {
    "total": 2000000
  }
}
```

---

#### Create Report by Pocket

```
POST /pockets/{id}/create-report
```

🔒 Requires authentication.

Report generation runs as a **background job**. Once the job completes, the generated `.xlsx` file can be downloaded via the link returned.

**Request body:**

```json
{
  "type": "INCOME",
  "date": "2026-01-01"
}
```

| Field | Type | Values |
|-------|------|--------|
| `type` | string | `INCOME`, `EXPENSE` |
| `date` | string | `YYYY-MM-DD` |

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Report sedang dibuat. Silahkan check berkala pada link berikut.",
  "data": {
    "link": "http://localhost:8000/reports/<uuid>-<timestamp>"
  }
}
```

---

### Incomes

#### Create Income

Adding an income **increases** the linked pocket balance.

```
POST /incomes
```

🔒 Requires authentication.

**Request body:**

```json
{
  "pocket_id": "uuid",
  "amount": 300000,
  "notes": "Menemukan uang di jalan"
}
```

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Berhasil menambahkan income.",
  "data": {
    "id": "income_id",
    "pocket_id": "pocket_id",
    "current_balance": 2300000
  }
}
```

---

### Expenses

#### Create Expense

Adding an expense **decreases** the linked pocket balance.

```
POST /expenses
```

🔒 Requires authentication.

**Request body:**

```json
{
  "pocket_id": "uuid",
  "amount": 2000000,
  "notes": "Ganti lecet mobil orang"
}
```

**Response:**

```json
{
  "status": 200,
  "error": false,
  "message": "Berhasil menambahkan expense.",
  "data": {
    "id": "expense_id",
    "pocket_id": "pocket_id",
    "current_balance": 300000
  }
}
```

---

### Reports

#### Download Report

```
GET /reports/{id}
```

Streams and downloads the generated `<id>.xlsx` report file from local storage.
