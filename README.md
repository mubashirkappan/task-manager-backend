# Laravel 11 Task Management API

Stateless, containerized REST API built with **Laravel 11**, featuring **JWT authentication**, **UUIDv4 primary keys**, and **Role-Based Access Control (RBAC)**.

---

## Architecture & Features

- **Authentication:** Stateless token-based auth with `php-open-source-saver/jwt-auth`.
- **Primary Keys:** UUIDv4 keys across all models (`HasUuids`).
- **Authorization & RBAC:**
  - **Admin:** Global CRUD access to all users' tasks.
  - **User:** Strict tenant isolation; access restricted to self-created tasks.
- **Task Management:** Full CRUD with title search, status filters (`pending`, `in_progress`, `completed`), and server-side pagination.
- **Docker Environment:** Multi-container setup with PHP 8.4-FPM, Nginx, and MySQL 8.0.
- **Automated Testing:** Pest PHP feature test suite verifying RBAC and tenant boundaries.
- **CI/CD:** GitHub Actions workflow executing migrations and tests on every Pull Request and push to `main`.

---

## Directory Structure


```text
backend/
├── app/
│   ├── Enums/UserRole.php          # Admin and User role definitions
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php      # Login, Register, Me, Refresh, Logout
│   │   └── TaskController.php      # Task CRUD operations
│   ├── Http/Requests/              # StoreTaskRequest, UpdateTaskRequest, RegisterRequest
│   ├── Http/Resources/             # TaskResource
│   ├── Models/                     # User, Task (HasUuids)
│   └── Policies/TaskPolicy.php     # RBAC policy rules
├── database/
│   ├── migrations/                 # UUID migrations for users and tasks
│   └── seeders/                    # UserSeeder, TaskSeeder, DatabaseSeeder
├── docker/
│   ├── nginx/default.conf          # Nginx FastCGI configuration
│   └── php/
│       ├── Dockerfile              # PHP 8.4-FPM Alpine image
│       └── local.ini               # Custom PHP configurations
├── postman/
│   ├── Laravel_Tasks_API.postman_collection.json
│   ├── Local.postman_environment.json
│   └── Live.postman_environment.json
├── routes/
│   └── api.php                     # API route definitions
├── tests/
│   └── Feature/TaskRbacTest.php    # Pest authorization & isolation tests
├── .github/workflows/ci-cd.yml     # Automated CI/CD pipeline
├── docker-compose.yml
└── .env.example


## Environment Configuration

   cp .env.example .env
    
    or add this to your `.env` file:
     
    APP_NAME=Laravel
    APP_ENV=local
    APP_KEY=
    APP_DEBUG=true
    APP_URL=http://localhost:8000
    FRONTEND_URL=http://localhost:5173
    
    # Database configuration (Docker defaults)
    DB_CONNECTION=mysql
    DB_HOST=db
    DB_PORT=3306
    DB_DATABASE=backend
    DB_USERNAME=sail
    DB_PASSWORD=password
    
    # JWT Settings
    JWT_SECRET=
    JWT_ALGO=HS256
    JWT_TTL=60
    JWT_REFRESH_TTL=20160  

1. Running with Docker Compose (Recommended)

    docker compose up -d --build

    This starts:

        web: Nginx proxy listening on http://localhost:8000

        app: PHP 8.4-FPM running on internal port 9000

        db: MySQL 8.0 accessible externally on port 3307 (mapped internally to 3306 to prevent conflicts with native local MySQL)
    
    Verify that all containers are running:

        docker compose ps

2. Install PHP Dependencies

    docker compose exec app composer install

3. Generate Encryption & JWT Keys

    docker compose exec app php artisan key:generate
    docker compose exec app php artisan jwt:secret

4. Run Migrations & Seed Database

    docker compose exec app php artisan migrate --seed

5. Check API Status

    http://localhost:8000/api


## Running Natively (Without Docker)
    
    Prerequisites
        PHP 8.4+ with pdo_mysql, mbstring, bcmath, intl, zip

        Composer 2+

        MySQL 8.0+    

    steps
        # 1. Install dependencies
        composer install

        # 2. Setup environment & keys
        cp .env.example .env
        php artisan key:generate
        php artisan jwt:secret --force

        # 3. Create the database in MySQL:
        # CREATE DATABASE backend;

        # 4. Run migrations and seed data
        php artisan migrate:fresh --seed

        # 5. Serve the API
        php artisan serve --port=8000

##Seeded Test Accounts
    name       email                  password         
    
    Admin       admin@example.com       password123 
    User 1      user1@example.com       password123 
    User 2      user2@example.com       password123 

## Automated Testing

    Inside Docker:
    
        docker compose exec app ./vendor/bin/pest   

    Natively:
    
        ./vendor/bin/pest

## Postman API Collection

    Postman schemas and environment configs are stored in the /postman directory:

        postman/Laravel_Tasks_API.postman_collection.json

        postman/Local.postman_environment.json

        postman/Live.postman_environment.json


## Common Docker Commands
Command                                                         Action 
docker compose up -d                                Start containers in background

docker compose down                                 Stop and remove containers

docker compose restart web                          Restart Nginx web server

docker compose logs -f app                          View live PHP-FPM container logs

docker compose exec app bash                        Open a bash shell inside the PHP container

docker compose exec app php artisan <cmd>           Run Artisan commands inside the container
