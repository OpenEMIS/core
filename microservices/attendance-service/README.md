# Attendance Service

## Overview

This is a Node.js microservice designed to handle all operations related to student and staff attendance for the OpenEMIS platform. It provides a centralized service for recording, retrieving, and managing attendance data.

## Features

-   **Centralized Attendance Logic**: Provides a single source of truth for attendance data.
-   **RESTful API**: Exposes a clear and consistent API for managing attendance records.
-   **Scalable**: As a separate microservice, it can be scaled independently of the main application.

## Prerequisites

-   Node.js (v14 or higher)
-   NPM
-   Access to the OpenEMIS database.

## Setup and Installation

1.  **Navigate to the service directory:**
    ```bash
    cd microservices/attendance-service
    ```

2.  **Install dependencies:**
    ```bash
    npm install
    ```

3.  **Configure environment variables:**
    Create a `.env` file by copying the example file:
    ```bash
    cp .env.example .env
    ```
    Now, edit the `.env` file with the correct database connection details.

## Running the Service

-   **Production mode:**
    ```bash
    npm start
    ```

-   **Development mode (with auto-restarting):**
    ```bash
    npm run dev
    ```

The service will start on the port specified in your `.env` file (default: 3006).

## API Usage

The API will be versioned (e.g., `/api/v1`).

### Student Attendance

*   **GET /api/v1/students/{studentId}/attendance**
    *   Description: Get all attendance records for a specific student for a given date range.
    *   Query Params: `startDate`, `endDate`.
    *   Response: An array of attendance objects.

*   **GET /api/v1/classes/{classId}/attendance**
    *   Description: Get all attendance records for a specific class for a given date.
    *   Query Params: `date`.
    *   Response: An array of student attendance objects for the class.

*   **POST /api/v1/classes/{classId}/attendance**
    *   Description: Record or update attendance for one or more students in a class.
    *   Body: An array of objects, each containing `studentId`, `date`, `absenceTypeId`, `reasonId`, `comment`.
    *   Response: A summary of the created/updated records.

### Staff Attendance

*   **GET /api/v1/staff/{staffId}/attendance**
    *   Description: Get all attendance records for a specific staff member for a given date range.
    *   Query Params: `startDate`, `endDate`.
    *   Response: An array of attendance objects.

*   **POST /api/v1/staff/{staffId}/attendance**
    *   Description: Record a new attendance event for a staff member (e.g., clock-in or clock-out).
    *   Body: An object containing `timestamp`, `eventType` ('in' or 'out').
    *   Response: The created attendance record.

### Supporting Resources

*   **GET /api/v1/absence-types**
    *   Description: Get a list of all possible absence types (e.g., Excused, Unexcused, Late).
    *   Response: An array of absence type objects.

*   **GET /api/v1/absence-reasons**
    *   Description: Get a list of all possible absence reasons (e.g., Illness, Appointment).
    *   Response: An array of absence reason objects.
