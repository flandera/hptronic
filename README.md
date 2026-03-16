## HPtronic E‑Commerce Backend

Headless Symfony backend (API only) for the HPtronic e‑commerce project, running on PHP 8.4 with Docker and MariaDB.

### Prerequisites

- Docker and Docker Compose installed
- (Optional) Local PHP 8.4 and Composer if you want to run tools outside Docker

### First time setup

1. Clone this repository into your workspace.
2. Create your local environment file from the example:

```bash
cp .env.example .env
```

You can then adjust `DATABASE_URL`, `APP_SECRET`, and other variables as needed for your local development setup. Do **not** commit your `.env` file.

3. Build and start the containers:

```bash
docker compose up --build
```

This will:
- Build a PHP 8.4 + Apache image
- Start the Symfony app on `http://localhost:8080`
- Start MariaDB on `localhost:3307`

### Installing PHP dependencies

The PHP dependencies (including Doctrine bundles, PHPStan, PHPCS, etc.) are managed by Composer.

From your host, install them **inside the app container**:

```bash
docker compose exec app bash -lc "composer install"
```

Run this after changing `composer.json` (for example when adding Doctrine, Slevomat, etc.) to make sure the bundles like `DoctrineBundle` are actually available.

### Application URLs

- API base URL (inside browser): `http://localhost:8080`

### Database schema & migrations

All persistence is handled via Doctrine ORM and migrations.

- **Generate a migration** (after changing entities):

```bash
docker compose exec app bash -lc "php bin/console make:migration"
```

- **Apply migrations** (create/update tables in the local MariaDB):

```bash
docker compose exec app bash -lc "php bin/console doctrine:migrations:migrate"
```

This will create all tables for `product`, `cart`, `cart_item`, `order` and `order_item`.

### Seeding demo data (fixtures)

The project includes Doctrine fixtures to make the API testable immediately:

- `ProductFixtures`: seeds a few demo products (e.g. `SKU-001`, `SKU-002`, `SKU-003`).
- `CartFixtures`: seeds a demo cart:
  - Cart ID: `demo-cart-1`
- `OrderFixtures`: seeds a demo order created from the demo cart:
  - Order ID: `demo-order-1`

Run all fixtures against your local database:

```bash
docker compose exec app bash -lc "php bin/console doctrine:fixtures:load"
```

After that you can call, for example:

- `GET /api/cart/demo-cart-1`
- `GET /api/orders/demo-order-1`

### Database connection

Default connection (configured via `DATABASE_URL` in `docker-compose.yml`):

- Host: `localhost`
- Port: `3307`
- Database: `hptronic`
- User: `symfony`
- Password: `symfony`

### Running quality tools

All tools are wired via Composer scripts and can be run either **inside the app container** or **from your host**.

#### From inside the container (recommended)

Open a shell in the `app` container:

```bash
docker compose exec app bash
```

Then run:

- **Static analysis (PHPStan, level max)**:

  ```bash
  composer phpstan
  ```

- **Coding standard check (PHPCS + Slevomat, PSR‑12)**:

  ```bash
  composer phpcs
  ```

- **Automatic code fixer (PHPCBF) for CS issues**:

  ```bash
  vendor/bin/phpcbf src tests
  ```

- **Tests (PHPUnit via Symfony PHPUnit Bridge)**:

  ```bash
  composer phpunit
  ```

#### From the host (no interactive shell)

You can also invoke the same commands directly from your host:

- **PHPStan**:

  ```bash
  docker compose exec app bash -lc "composer phpstan"
  ```

- **PHPCS**:

  ```bash
  docker compose exec app bash -lc "composer phpcs"
  ```

- **PHPCBF (auto‑fix coding style)**:

  ```bash
  docker compose exec app bash -lc "vendor/bin/phpcbf src tests"
  ```

- **PHPUnit**:

  ```bash
  docker compose exec app bash -lc 'composer phpunit'
  ```

These commands all run inside the PHP 8.4 container, so they are independent of the PHP version installed on your host.

### Local (non‑Docker) usage (optional)

If you have PHP 8.4 and Composer installed locally, you can run:

```bash
composer install
composer phpstan
composer phpcs
composer phpunit
```
