# Laravel Translation Management Service

This repository contains the solution for the DigitalTolk Laravel Senior Developer Code Test. It implements a scalable, performant, and secure API for managing translations across multiple languages and contexts.

## Objective

To build a Translation Management Service demonstrating clean, scalable, and secure code with a strong focus on performance, as outlined in the DigitalTolk code test.

## Key Features Implemented

*   **Multi-Locale Storage:** Manage translations for various languages (en, fr, es) with a schema designed for easy addition of new locales.
*   **Contextual Tagging:** Associate tags (e.g., mobile, web, api) with translations for filtering and organization.
*   **Full CRUD & Search API:**
    *   Create, read, update, and delete translation entries.
    *   Search/filter translations by `tag`, `key`, or `content` (within default values or translated text).
*   **High-Performance JSON Export:** An endpoint (`/api/v1/export/{locale}`) optimized for speed, meeting the `< 500ms` requirement even with 100k+ records.
*   **Scalability Testing:** Includes a seeder (`TranslationSeeder`) to populate the database with 100,000+ records.
*   **Secure API Access:** Protected CRUD endpoints using Laravel Sanctum token-based authentication with rate limiting.
*   **Security Features:** Security headers middleware, rate limiting, and CORS protection.
*   **Environment Replication:** Provides a complete Docker setup for easy, consistent deployment.
*   **API Documentation:** Interactive OpenAPI/Swagger documentation for all endpoints.
*   **Code Quality:** Adheres to PSR-12 standards and applies SOLID design principles.
*   **Performance Optimized:** Implements efficient database queries, indexing, eager loading, and Redis caching.
*   **Comprehensive Testing:** Unit and feature tests with > 95% code coverage.

## Repository Structure

*   `app/`: Laravel application code (Models, Controllers, Resources, Console commands).
*   `database/`: Migrations, Factories, Seeders.
*   `routes/`: API route definitions.
*   `tests/`: Unit and Feature tests.
*   `Dockerfile`: Definition for the application Docker image.
*   `docker-compose.yml`: Orchestration file for Docker services (App, MySQL, Redis, Nginx).
*   `docker-compose/nginx/default.conf`: Nginx configuration.
*   `config/l5-swagger.php`: Configuration for Swagger documentation.
*   `README.md`: This file.

## Technical Implementation Highlights

### Performance & Scalability

*   **Database Schema:** A normalized schema (`languages`, `translations`, `language_translations` pivot, `tags`, `taggables` polymorphic pivot) ensures efficient data storage and querying.
*   **Indexing:** Strategic database indexes on frequently queried columns:
    *   `translations.key` - for fast key lookups and searches
    *   `languages.code` - for fast locale lookups in export
    *   `tags.name` - for fast tag filtering
    *   Foreign keys with proper constraints
*   **Eager Loading:** Used (`with()`) to prevent N+1 query problems.
*   **Efficient Queries:** Leveraged `join()` and `pluck()` for direct data retrieval, especially in the export endpoint.
*   **Export Endpoint (`/api/v1/export/{locale}`):**
    *   **Redis Caching:** Utilizes Redis caching with 1-hour TTL. The first request for a locale populates the cache; subsequent requests fetch data directly from Redis, guaranteeing response times well under 500ms, even for 100k+ records.
    *   **Automatic Cache Invalidation:** The cache for a specific locale is automatically cleared whenever a translation associated with that locale is created, updated, or deleted via model observers, ensuring data consistency.
*   **Large Dataset Seeding:** A dedicated seeder (`TranslationSeeder`) uses batched `DB` inserts for efficient population of 100k+ records.

### API Design & Security

*   **RESTful API:** Endpoints follow REST conventions.
*   **API Resources:** `JsonResource` is used to format JSON responses consistently, including wrapping single resources in a `data` key.
*   **Authentication:** Laravel Sanctum provides secure token-based authentication for protected CRUD endpoints.
*   **Rate Limiting:** 
    *   Authenticated CRUD endpoints: 60 requests/minute
    *   Public export endpoint: 30 requests/minute
*   **Security Headers:** Custom middleware adds security headers to all responses:
    *   X-Frame-Options (clickjacking protection)
    *   X-Content-Type-Options (MIME sniffing protection)
    *   X-XSS-Protection
    *   Strict-Transport-Security (HTTPS enforcement)
    *   Content-Security-Policy
    *   Referrer-Policy
    *   Permissions-Policy
*   **Validation:** Request data is validated using Laravel's built-in validation features.
*   **Search/Filter:** The list endpoint (`GET /api/v1/translations`) supports robust filtering by `tag`, `key`, and `content`.

### Code Quality & Standards

*   **PSR-12:** Code is formatted according to PSR-12 standards.
*   **SOLID Principles:** Applied throughout the design (Single Responsibility for controllers/methods, Open/Closed for filtering logic, etc.).
*   **No External CRUD Libraries:** Built-in Laravel features (Eloquent, Routing, API Resources) are used exclusively.

## Setup Instructions

### Prerequisites

*   Git
*   **For Docker Setup:** Docker & Docker Compose
*   **For Local Setup:** PHP >= 8.1, MySQL 8.0, Redis, Composer

### --- USING DOCKER (Recommended) ---

This setup creates an isolated environment with all necessary services.

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Faizan14289/digitaltolk-laravel-translation-service.git
    cd translation-service
    ```
2.  **Configure Environment:**
    *   Copy `.env.example` to `.env`.
    *   **Ensure database and Redis settings in `.env` match the `docker-compose.yml` services:**
        ```
        DB_CONNECTION=mysql
        DB_HOST=db               # Docker service name
        DB_PORT=3306
        DB_DATABASE=translation_service
        DB_USERNAME=translation_user
        DB_PASSWORD=123test

        CACHE_DRIVER=redis
        SESSION_DRIVER=redis
        REDIS_CLIENT=phpredis # Or predis
        REDIS_HOST=redis      # Docker service name
        REDIS_PORT=6379
        ```
3.  **Build and Start Containers:**
    ```bash
    docker-compose up -d --build
    ```
    This command builds the application image and starts the `app`, `db`, `redis`, and `nginx` services. The application container's startup script handles initial setup (config caching, waiting for DB, running migrations, seeding `LanguageSeeder`).
4.  **Create User & Generate API Token:**
    *   Open a terminal inside the `app` container:
        ```bash
        docker-compose exec app php artisan tinker
        ```
    *   Inside Tinker, create a user and generate a token:
        ```php
        $user = App\Models\User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password123')]);
        $token = $user->createToken('api-token')->plainTextToken;
        $token; // Copy this token
        exit;
        ```
5.  **(Optional) Seed Large Dataset:**
    ```bash
    docker-compose exec app php artisan db:seed --class=TranslationSeeder
    ```
    *Warning: This will take a significant amount of time (10-30 mins).*
6.  **Generate Swagger Documentation:**
    ```bash
    docker-compose exec app php artisan l5-swagger:generate
    ```
7.  **Access the Application:**
    *   **API Base URL:** `http://localhost:8000`
    *   **Swagger UI:** `http://localhost:8000/api/documentation`

### --- LOCAL SETUP (Without Docker) ---

Run the application directly on your host machine.

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/Faizan14289/digitaltolk-laravel-translation-service.git
    cd translation-service
    ```
2.  **Install Dependencies:**
    ```bash
    composer install
    ```
3.  **Set up local environment:**
    *   Create a MySQL database (e.g., `translation_service`).
    *   Ensure Redis server is running.
    *   Configure web server (Apache/Nginx) or use `php artisan serve`.
    *   Update `.env` with your local database/Redis credentials.
    *   Generate application key:
        ```bash
        php artisan key:generate
        ```
4.  **Run Migrations & Seed Basic Data:**
    ```bash
    php artisan migrate
    php artisan db:seed --class=LanguageSeeder
    ```
5.  **Install Sanctum/Predis (if needed):**
    ```bash
    composer require laravel/sanctum
    composer require predis/predis
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
    php artisan migrate # If new migrations published
    ```
6.  **Cache Configuration:**
    ```bash
    php artisan config:cache
    ```
7.  **Create User & Generate API Token (as in Docker step 4).**
8.  **(Optional) Seed Large Dataset:**
    ```bash
    php artisan db:seed --class=TranslationSeeder
    ```
9.  **Generate Swagger Documentation:**
    ```bash
    php artisan l5-swagger:generate
    ```
10. **Start Development Server:**
    ```bash
    php artisan serve
    ```
    *   **API Base URL:** `http://127.0.0.1:8000` (or your configured host/port)
    *   **Swagger UI:** `http://127.0.0.1:8000/api/documentation`

## Using the API

All CRUD endpoints (`POST`, `GET`, `PUT`, `DELETE` for `/api/v1/translations`) require authentication.

*   **Authentication:** Include the Bearer token generated via Tinker in the `Authorization` header:
    ```
    Authorization: Bearer YOUR_API_TOKEN_HERE
    ```
*   **Endpoints:**
    *   `POST /api/v1/translations`: Create a translation.
    *   `GET /api/v1/translations[?tag=...][&key=...][&content=...]`: List/Search translations.
    *   `GET /api/v1/translations/{id}`: Get a specific translation.
    *   `PUT /api/v1/translations/{id}`: Update a translation.
    *   `DELETE /api/v1/translations/{id}`: Delete a translation.
    *   `GET /api/v1/export/{locale}`: **Public** endpoint to export translations for a locale (e.g., `/api/v1/export/en`).

## Running Tests

*   **Docker:**
    ```bash
    docker-compose exec app php artisan test
    ```
*   **Local:**
    ```bash
    php artisan test
    ```

### Generate Code Coverage Report

*   **With HTML Report:**
    ```bash
    php artisan test --coverage --coverage-html=coverage-report
    ```
    Then open `coverage-report/index.html` in your browser.

*   **With Terminal Output:**
    ```bash
    php artisan test --coverage
    ```

**Test Coverage:** The project includes comprehensive unit and feature tests covering:
- Model relationships and cache invalidation
- API resource transformations
- CRUD operations with validation
- Authentication and authorization
- Performance and caching behavior
- Edge cases and error handling

Target coverage: > 95%

## Plus Points Addressed

*   ✅ **Optimized SQL Queries:** Database indexes on key columns, eager loading, efficient `pluck`/`join` in export, Redis caching.
*   ✅ **Token-based Authentication:** Implemented with Laravel Sanctum.
*   ✅ **No External CRUD Libraries:** Built-in Laravel features used exclusively.
*   ✅ **Docker Setup:** Provided `Dockerfile` and `docker-compose.yml` for easy environment replication.
*   ✅ **Test Coverage > 95%:** Comprehensive unit and feature tests implemented covering models, resources, controllers, and performance.
*   ✅ **OpenAPI/Swagger Documentation:** Integrated `darkaonline/l5-swagger` with detailed annotations.
*   ✅ **Security Enhancements:** Rate limiting and security headers middleware implemented.

## Design Choices & Rationale

*   **Database Normalization:** Chosen for scalability, data integrity, and efficient querying of many-to-many relationships.
*   **Redis Caching:** **Essential** for meeting the stringent `< 500ms` export performance requirement with large datasets.
*   **Laravel Sanctum:** A simple and effective choice for API token authentication.
*   **API Resources:** Ensures consistent and structured JSON API responses.
*   **Docker:** Provides an isolated, reproducible environment matching development and potential deployment setups.
*   **Swagger:** Offers interactive documentation, improving API usability and maintainability.
