# Attendance Service

## Overview

This is a Node.js microservice designed to manage student attendance for the Ethiopian Ministry of Education's digital education system. It provides endpoints to mark attendance and retrieve attendance records for students and classes.

This service is intended to be run as part of a larger microservices ecosystem. It requires a connection to a central "core" system to validate the existence of students and classes before recording any data. It manages its own database to store attendance records.

## Features

-   **Attendance Tracking**: Endpoints to mark students as 'present', 'absent', 'late', or 'excused'.
-   **Data Validation**: Communicates with the core education system to validate student/class IDs before storing data.
-   **Record Retrieval**: Endpoints to fetch attendance history for a specific student or an entire class on a given day.
-   **Standalone Database**: Manages its own database table for attendance records, ensuring service independence.
-   **Configurable**: All database connections, API keys, and service ports are configured via environment variables.

## Prerequisites

-   Node.js (v14 or higher)
-   NPM
-   A running MySQL database server.

## Setup and Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd attendance-service
    ```

2.  **Install dependencies:**
    ```bash
    npm install
    ```

3.  **Database Setup:**
    This service requires its own database. Connect to your MySQL server and run the commands in `config/schema.sql` to create the database, user, and the `attendance_records` table. You can also run the DDL statement manually:
    ```sql
    CREATE TABLE `attendance_records` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `student_id` INT UNSIGNED NOT NULL,
      `class_id` INT UNSIGNED NOT NULL,
      `attendance_date` DATE NOT NULL,
      `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL,
      `notes` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uq_student_class_date` (`student_id`, `class_id`, `attendance_date`),
      INDEX `idx_student_id` (`student_id`),
      INDEX `idx_class_id_date` (`class_id`, `attendance_date`)
    );
    ```

4.  **Configure environment variables:**
    Create a `.env` file in the root of the project by copying the example file:
    ```bash
    cp .env.example .env
    ```
    Now, edit the `.env` file with your specific configuration for both the Attendance Service's own database and the connection to the core system.

## Running the Service

-   **Production mode:**
    ```bash
    npm start
    ```

-   **Development mode (with auto-restarting):**
    ```bash
    npm run dev
    ```

The service will start on the port specified in your `.env` file (default: 3002).

## API Usage

### 1. Mark Student Attendance

-   **Endpoint**: `POST /api/attendance`
-   **Description**: Creates or updates an attendance record for a student on a specific date. If a record for that student, class, and date already exists, it will be updated (idempotent).
-   **Request Body** (JSON):
    ```json
    {
        "student_id": 101,
        "class_id": 22,
        "attendance_date": "2023-10-27",
        "status": "present",
        "notes": "Arrived on time."
    }
    ```
-   **Success Response** (201 Created):
    ```json
    {
        "message": "Attendance marked successfully.",
        "data": {
            "success": true,
            "student_id": 101,
            "class_id": 22,
            "attendance_date": "2023-10-27",
            "status": "present"
        }
    }
    ```

### 2. Get Attendance History for a Student

-   **Endpoint**: `GET /api/attendance/student/:studentId`
-   **Description**: Retrieves a list of all attendance records for a single student, ordered by date descending.
-   **Example**: `GET http://localhost:3002/api/attendance/student/101`
-   **Success Response** (200 OK):
    ```json
    {
        "data": [
            {
                "id": 1,
                "student_id": 101,
                "class_id": 22,
                "attendance_date": "2023-10-27T00:00:00.000Z",
                "status": "present",
                "notes": "Arrived on time.",
                "created_at": "2023-10-27T10:00:00.000Z",
                "updated_at": "2023-10-27T10:00:00.000Z"
            },
            ...
        ]
    }
    ```

### 3. Get Attendance for a Class on a Specific Date

-   **Endpoint**: `GET /api/attendance/class/:classId`
-   **Description**: Retrieves all attendance records for a given class on a specific date. The date must be provided as a query parameter.
-   **Query Parameter**:
    -   `date` (required): The date in `YYYY-MM-DD` format.
-   **Example**: `GET http://localhost:3002/api/attendance/class/22?date=2023-10-27`
-   **Success Response** (200 OK):
    ```json
    {
        "data": [
            {
                "id": 1,
                "student_id": 101,
                "class_id": 22,
                ...
            },
            {
                "id": 2,
                "student_id": 102,
                "class_id": 22,
                ...
            }
        ]
    }
    ```
