# AI Service

## Overview

This is a Node.js microservice designed to perform AI-driven and analytical tasks for the Ethiopian Ministry of Education's digital education system. Its primary function is to consume data from other microservices (like the Exam and Attendance services) to generate insights.

The first feature implemented in this service is the **At-Risk Student Identification** model. This model analyzes student performance and attendance data to flag students who may need academic intervention.

## Features

-   **Data-Driven Insights**: Consumes data from multiple service databases to provide a holistic view of student performance.
-   **At-Risk Student Analysis**: Implements a configurable rules-based model to identify students with poor exam scores or high rates of absence.
-   **Centralized Analysis**: Provides a single endpoint to retrieve the list of at-risk students.
-   **Configurable Logic**: The thresholds for what constitutes "at-risk" (e.g., exam score percentage, number of absences) are configurable via environment variables.

## Prerequisites

-   Node.js (v14 or higher)
-   NPM
-   Read-only access to the databases for the **Core OpenEMIS system**, the **Attendance Service**, and the **Exam Service**.

## Setup and Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd ai-service
    ```

2.  **Install dependencies:**
    ```bash
    npm install
    ```

3.  **Configure environment variables:**
    This service requires connections to multiple databases. Create a `.env` file in the root of the project by copying the example file:
    ```bash
    cp .env.example .env
    ```
    Now, edit the `.env` file with the correct connection details for all required databases and adjust the analysis thresholds as needed.

## Running the Service

-   **Production mode:**
    ```bash
    npm start
    ```

-   **Development mode (with auto-restarting):**
    ```bash
    npm run dev
    ```

The service will start on the port specified in your `.env` file (default: 3005).

## API Usage

### Get At-Risk Students

-   **Endpoint**: `GET /api/analysis/at-risk-students`
-   **Description**: Triggers the at-risk analysis and returns a list of students who meet the criteria defined by the `EXAM_SCORE_THRESHOLD` and `ATTENDANCE_ABSENCE_THRESHOLD` environment variables.
-   **Success Response** (200 OK):
    ```json
    {
        "message": "Found 2 at-risk student(s).",
        "data": [
            {
                "student_id": 101,
                "first_name": "John",
                "last_name": "Doe",
                "email": "john.doe@example.com",
                "at_risk_reasons": [
                    "Failed exam 1 with score 45.00%",
                    "Has 6 unexcused absences"
                ]
            },
            {
                "student_id": 105,
                "first_name": "Jane",
                "last_name": "Smith",
                "email": "jane.smith@example.com",
                "at_risk_reasons": [
                    "Has 8 unexcused absences"
                ]
            }
        ]
    }
    ```
