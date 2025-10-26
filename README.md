# Client Management System

## Project Overview

This project is a **Client Management System** built with **Laravel 12**.
It allows managing clients, importing/exporting CSV data, detecting duplicates, and filtering/sorting client data.

### Features Implemented

* Import large CSV files and detect duplicates automatically.
* List clients with search, filter, and sorting functionality.
* Show client details along with their duplicate entries.
* Export clients to CSV, with an option to include duplicates only.
* Support for massive CSV imports for stress testing.
* Unit and feature tests for all major functionalities.

---

## Prerequisites

* **PHP**: >= 8.2
* **Laravel**: 12
* **Database**: MySQL (development)
* Composer for PHP dependencies.

---

## Installation Instructions

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Install PHP dependencies**

   ```bash
   composer install

3. **Copy `.env` file**

   ```bash
   cp .env.example .env
   ```

4. **Set environment variables**

   * Update `.env` with your database credentials:

     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=your_database
     DB_USERNAME=root
     DB_PASSWORD=
     ```

5. **Generate application key**

   ```bash
   php artisan key:generate
   ```

---

## Database Setup

1. **Run migrations**

   ```bash
   php artisan migrate
   ```

2. **Run seeders (optional)**

   ```bash
   php artisan db:seed
   ```

---

## Running the Application

1. **Start Laravel server**

   ```bash
   php artisan serve
   ```
2. **Access application**

   ```
   http://127.0.0.1:8000
   ```

---

## Running Tests

* **Run all tests**

  ```bash
  php artisan test
  ```
* **Run only Feature tests**

  ```bash
  php artisan test --testsuite=Feature
  ```
* **Run only Unit tests**

  ```bash
  php artisan test --testsuite=Unit
  ```

> Note: Tests use **SQLite in-memory** database by default. Ensure `.env.testing` or `phpunit.xml` has:
>
> ```xml
> <env name="DB_CONNECTION" value="mysql"/>
><env name="DB_PORT" value="3306"/>
><env name="DB_DATABASE" value="your_db"/>
><env name="DB_USERNAME" value="your_username"/>
><env name="DB_PASSWORD" value="your_secret"/>
> ```

---

## API Documentation

* **Import clients**: `POST /api/clients/import` (CSV upload)
* **List clients**: `GET /api/clients`
* **Show client details**: `GET /api/clients/{id}`
* **Export clients**: `GET /api/clients/export?duplicates_only=1`
* Supports CSV export with all clients or duplicates only.

> You can import this collection into **Postman** for testing.

---

## Code Structure and Complex Logic

* **ClientExportService**
  Handles exporting clients to CSV. Supports filtering duplicates only.

  * `export(ClientExportDTO $dto)`: Main function that builds query based on DTO.
  * `toCsv($rows)`: Converts collection to CSV string.

* **Duplicate Detection**

  * Duplicate root entries are detected using SQL:

    ```sql
    SELECT MIN(id)
    FROM clients
    GROUP BY company_name, email, phone_number
    HAVING COUNT(*) > 1;
    ```
  * Duplicates are those with `duplicate_group_id` set or matching a duplicate root.

* **Tests**

  * Feature tests cover importing, exporting, listing, filtering, and duplicates.
  * PHPUnit 12 attributes are used instead of deprecated doc-block `@test`.

---

## Architecture Decisions & Trade-offs

* **Service-based architecture**: All client-related logic (index, show, export) handled in a **dedicated service** for reusability.
* **DTOs**: Filter and export criteria are passed via **Data Transfer Objects**, decoupling controllers from business logic.
* **CSV Export**: Stored in `storage/app/private/exports` and returned via download.
* **MYSQL for testing**: To speed up writing script
---

## Notes

* Make sure all client duplicates have the **same email and phone number** for proper detection.

---

## License

[MIT License](LICENSE)
