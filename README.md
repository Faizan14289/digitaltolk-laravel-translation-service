# Laravel Translation Management Service

This is a solution for the DigitalTolk Laravel Senior Developer Code Test.

## Objective

Build a scalable, performant, and secure Translation Management Service API.

## Features Implemented

*   Store translations for multiple locales.
*   Tag translations for context.
*   CRUD operations for translations via API.
*   Search translations by tags, keys, or content.
*   JSON export endpoint for frontend consumption.
*   Seeding for 100k+ records.
*   Token-based authentication (Sanctum).
*   Docker setup.
*   API Resource-based responses.
*   PSR-12 adherence.
*   SOLID principles applied.
*   OpenAPI/Swagger Documentation.

## Requirements

### For Docker Setup:
*   Docker & Docker Compose

### For Local Setup:
*   PHP >= 8.1
*   MySQL 8.0 (or compatible)
*   Redis
*   Composer

## Setup Instructions

### Docker Setup (Recommended)

This setup uses Docker Compose to manage containers for the application, database, Redis, and web server.

1.  **Clone the repository:**
    ```bash
    git clone <your-repo-url>
    cd <your-project-directory>
    ```

2.  **Environment Configuration:**
    *   Copy `.env.example` to `.env`.
    *   **Crucially, ensure your `.env` file matches the services defined in `docker-compose.yml`.**
        *   **Database:**
            ```
            DB_CONNECTION=mysql
            DB_HOST=db              # Must match the 'db' service name in docker-compose.yml
            DB_PORT=3306            # Must match the exposed port in docker-compose.yml
            DB_DATABASE=translation_service # Must match MYSQL_DATABASE in docker-compose.yml
            DB_USERNAME=translation_user    # Must match MYSQL_USER in docker-compose.yml
            DB_PASSWORD=123test             # Must match MYSQL_PASSWORD in docker-compose.yml
            ```
        *   **Cache/Session (Redis):**
            ```
            CACHE_DRIVER=redis
            SESSION_DRIVER=redis
            REDIS_CLIENT=phpredis # Or predis if you install that package
            REDIS_HOST=redis      # Must match the 'redis' service name in docker-compose.yml
            REDIS_PORT=6379       # Must match the exposed port in docker-compose.yml
            ```
        *   *(Other settings like `APP_KEY`, `APP_URL` can be configured as needed)*

3.  **(If not using automatic build in docker-compose.yml) Install PHP Dependencies (inside the app container or on host for building image):**
    *   **Option 1 (Recommended if your Dockerfile handles it):** The `docker-compose up --build` command (Step 5) will use the `Dockerfile` to build the `app` image. If your `Dockerfile` includes `COPY --from=composer:latest /usr/bin/composer /usr/bin/composer` and `RUN composer install`, dependencies will be installed during the image build.
    *   **Option 2 (Manual install in container):** If you need to install/update dependencies after the container is built/running:
        ```bash
        docker-compose exec app composer install
        # Or, if you need to update/add packages:
        # docker-compose exec app composer update
        ```

4.  **Start Docker Containers:**
    ```bash
    docker-compose up -d --build
    ```
    This command builds the custom `app` image (including installing dependencies via the `Dockerfile`) and starts all services (`app`, `db`, `redis`, `nginx`) in detached mode. The `app` container's startup command should handle initial setup like config caching, waiting for the DB, running migrations, and seeding basic data (Languages).

5.  **Generate Application Key (if not handled automatically):**
    ```bash
    docker-compose exec app php artisan key:generate
    ```

6.  **Run Migrations (if not handled automatically):**
    ```bash
    docker-compose exec app php artisan migrate
    ```

7.  **Cache Configuration (important for Redis/Performance, if not handled automatically):**
    ```bash
    docker-compose exec app php artisan config:cache
    ```

8.  **Create a User and Generate an API Token (for Authentication):**
    To access the protected CRUD endpoints, you need a user with an API token.
    *   **Open Tinker inside the `app` container:**
        ```bash
        docker-compose exec app php artisan tinker
        ```
    *   **Inside the Tinker shell (`>>>`), create a user and generate a token:**
        ```php
        // Create a new user (or find an existing one)
        $user = App\Models\User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password123')]);
        // OR, if you have a specific user in mind and know their ID:
        // $user = App\Models\User::find(1);

        // Generate a personal access token for the user
        $token = $user->createToken('api-token')->plainTextToken;
        $token; // This will output the plain text token, e.g., "1|XWDzliUjFbbK88ooCzpBKEdtxJ8KHAOn01uB1qhNae779e7e"
        ```
    *   **Copy the generated token string (e.g., `1|XWDzliUjFbbK88ooCzpBKEdtxJ8KHAOn01uB1qhNae779e7e`). You will need this to authenticate API requests.**
    *   **Exit Tinker:**
        ```php
        exit
        ```

9.  **(Optional) Run Additional Seeders:**
    While the Docker setup might seed basic data automatically, you can manually run seeders or the large dataset seeder as needed:
    ```bash
    # Seed basic data (if not done automatically)
    docker-compose exec app php artisan db:seed --class=LanguageSeeder
    # Seed the large translation dataset (This will take a considerable amount of time, e.g., 10s of minutes)
    docker-compose exec app php artisan db:seed --class=TranslationSeeder
    ```

10. **Generate Swagger Documentation (Optional, but recommended):**
    ```bash
    docker-compose exec app php artisan l5-swagger:generate
    ```

11. **Access the Application:**
    The API should now be accessible at `http://localhost:8000`.

### Local Setup (Without Docker)

If you prefer to run the application directly on your host machine:

1.  **Clone the repository:**
    ```bash
    git clone <your-repo-url>
    cd <your-project-directory>
    ```

2.  **Install PHP Dependencies:**
    ```bash
    composer install
    ```
    *   If you need to update or add packages later:
        ```bash
        composer update
        # Or for a specific package:
        # composer require vendor/package
        ```

3.  **Set up your local environment:**
    *   **Database:** Create a MySQL database (e.g., `translation_service`).
    *   **Redis:** Ensure Redis server is running.
    *   **Web Server:** Configure a web server (Apache/Nginx) to point to the `public/` directory, or use Laravel's development server (`php artisan serve`).
    *   **Environment Configuration:**
        *   Copy `.env.example` to `.env`.
        *   Update the `.env` file with your local database and Redis credentials:
            ```
            DB_CONNECTION=mysql
            DB_HOST=127.0.0.1       # Or your DB host
            DB_PORT=3306            # Or your DB port
            DB_DATABASE=translation_service # Your local DB name
            DB_USERNAME=your_local_db_user
            DB_PASSWORD=your_local_db_password

            CACHE_DRIVER=redis
            SESSION_DRIVER=redis
            REDIS_CLIENT=phpredis # Or predis
            REDIS_HOST=127.0.0.1  # Or your Redis host
            REDIS_PORT=6379       # Or your Redis port
            ```
        *   Generate the application key:
            ```bash
            php artisan key:generate
            ```

4.  **Run Migrations:**
    ```bash
    php artisan migrate
    ```

5.  **Install Laravel Sanctum & Predis (if needed, and not in composer.json):**
    ```bash
    composer require laravel/sanctum
    composer require predis/predis # Or ensure phpredis extension is installed
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
    php artisan migrate # Run again if Sanctum migrations were published
    ```

6.  **Cache Configuration:**
    ```bash
    php artisan config:cache
    ```

7.  **Create a User and Generate an API Token (for Authentication):**
    To access the protected CRUD endpoints, you need a user with an API token.
    *   **Open Tinker:**
        ```bash
        php artisan tinker
        ```
    *   **Inside the Tinker shell (`>>>`), create a user and generate a token:**
        ```php
        // Create a new user (or find an existing one)
        $user = App\Models\User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com', 'password' => bcrypt('password123')]);
        // OR, if you have a specific user in mind and know their ID:
        // $user = App\Models\User::find(1);

        // Generate a personal access token for the user
        $token = $user->createToken('api-token')->plainTextToken;
        $token; // This will output the plain text token, e.g., "1|XWDzliUjFbbK88ooCzpBKEdtxJ8KHAOn01uB1qhNae779e7e"
        ```
    *   **Copy the generated token string (e.g., `1|XWDzliUjFbbK88ooCzpBKEdtxJ8KHAOn01uB1qhNae779e7e`). You will need this to authenticate API requests.**
    *   **Exit Tinker:**
        ```php
        exit
        ```

8.  **Seed the Database:**
    Populate the database with initial data and test datasets.
    ```bash
    # Seed basic reference data (languages, potentially tags)
    php artisan db:seed --class=LanguageSeeder

    # --- Populate with 100k+ Records for Scalability Testing ---
    # This command will populate the database with over 100,000 translation records.
    # WARNING: This process will take a significant amount of time (potentially 10s of minutes)
    # and consume considerable resources (CPU, RAM, Disk I/O).
    # Run this only if you need the large dataset for performance testing/scalability checks.
    # php artisan db:seed --class=TranslationSeeder
    # ---------------------------------------------

    # You can also run all seeders sequentially (if you have a DatabaseSeeder that calls others):
    # php artisan db:seed
    ```

9.  **Generate Swagger Documentation (Optional):**
    ```bash
    php artisan l5-swagger:generate
    ```

10. **Start the Development Server (or use your configured web server):**
    ```bash
    php artisan serve
    # Access at http://127.0.0.1:8000 (or configured host/port)
    ```

## API Endpoints

All CRUD endpoints require authentication via Laravel Sanctum Bearer token.

*   **POST** `/api/v1/translations` - Create a new translation.
    *   **Body (JSON):**
        ```json
        {
          "key": "messages.welcome",
          "default_value": "Welcome!",
          "translations": {
            "es": "¡Bienvenido!",
            "fr": "Bienvenue !"
          },
          "tags": ["web", "homepage"]
        }
        ```
*   **GET** `/api/v1/translations` - List translations (supports `?tag=...`, `?key=...`, `?content=...`).
*   **GET** `/api/v1/translations/{id}` - Get a specific translation.
*   **PUT/PATCH** `/api/v1/translations/{id}` - Update a specific translation.
    *   **Body (JSON):** Same structure as create, fields are optional for updates.
*   **DELETE** `/api/v1/translations/{id}` - Delete a specific translation.
*   **GET** `/api/v1/export/{locale}` - Export translations for a specific locale as JSON (e.g., `/api/v1/export/en`). This is a public endpoint.

## Using the API (Authentication)

To access the protected CRUD endpoints (`POST`, `GET`, `PUT`, `DELETE` for `/api/v1/translations`), you must include the Bearer token you generated using Tinker in the `Authorization` header of your API requests.

**Example using cURL:**

Replace `YOUR_API_TOKEN_HERE` with the token you copied from the Tinker output.

```bash
# Example: Create a new translation
curl -X POST http://localhost:8000/api/v1/translations \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_API_TOKEN_HERE" \
  -d '{
    "key": "api.test.message",
    "default_value": "This is a test message from the API.",
    "translations": {
      "es": "Este es un mensaje de prueba desde la API.",
      "fr": "Ceci est un message de test de l'\''API."
    },
    "tags": ["api", "test"]
  }'