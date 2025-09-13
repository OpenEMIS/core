# Exam Service

## Overview

This is a Node.js microservice designed to manage the entire lifecycle of exams for the Ethiopian Ministry of Education's digital education system. It handles the creation of exams, management of questions, submission of student answers, and automated grading.

This service operates with its own database to store all exam-related data and connects to a central "core" system to validate student information.

## Features

-   **Full Exam Lifecycle**: Endpoints to create exams, add questions, submit answers, and trigger grading.
-   **Complex Schema**: Manages related data across `exams`, `exam_questions`, `student_answers`, and `exam_results` tables.
-   **Data Validation**: Ensures student IDs are valid by checking against the core education system before processing submissions or results.
-   **Standalone Database**: Encapsulates all exam-related data within its own database, ensuring service independence.
-   **Configurable**: All external connections and settings are managed via environment variables.

## Prerequisites

-   Node.js (v14 or higher)
-   NPM
-   A running MySQL database server.

## Setup and Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd exam-service
    ```

2.  **Install dependencies:**
    ```bash
    npm install
    ```

3.  **Database Setup:**
    This service requires its own database with multiple tables. Connect to your MySQL server and run the DDL statements in `config/schema.sql` to create the database, user, and all necessary tables.

4.  **Configure environment variables:**
    Create a `.env` file in the root of the project by copying the example file:
    ```bash
    cp .env.example .env
    ```
    Now, edit the `.env` file with your specific configuration for both the Exam Service's own database and the connection to the core system.

## Running the Service

-   **Production mode:**
    ```bash
    npm start
    ```

-   **Development mode (with auto-restarting):**
    ```bash
    npm run dev
    ```

The service will start on the port specified in your `.env` file (default: 3003).

## API Usage

### Exam Management

#### 1. Create an Exam
-   **Endpoint**: `POST /api/exams`
-   **Description**: Creates a new exam record.
-   **Request Body**:
    ```json
    {
        "class_id": 3,
        "title": "Mid-term Physics Exam",
        "description": "Chapters 1-5.",
        "exam_date": "2023-11-15T10:00:00Z"
    }
    ```
-   **Success Response** (201): `{ "message": "Exam created...", "data": { ... } }`

#### 2. Get Exam Details
-   **Endpoint**: `GET /api/exams/:examId`
-   **Description**: Retrieves details for a single exam, including all of its questions (without the correct answers).
-   **Success Response** (200): `{ "data": { "id": 1, "title": "...", "questions": [...] } }`

### Question Management

#### 3. Add a Question to an Exam
-   **Endpoint**: `POST /api/exams/:examId/questions`
-   **Description**: Adds a new question to an existing exam.
-   **Request Body**:
    ```json
    {
        "question_text": "What is the formula for force?",
        "question_type": "short_answer",
        "options": null,
        "correct_answer": "F=ma",
        "points": 5
    }
    ```
-   **Success Response** (201): `{ "message": "Question added...", "data": { ... } }`

### Student Interaction

#### 4. Submit Student Answers
-   **Endpoint**: `POST /api/exams/:examId/submissions`
-   **Description**: Submits a batch of answers for a specific student for a given exam.
-   **Request Body**:
    ```json
    {
        "student_id": 123,
        "answers": [
            { "question_id": 1, "answer_text": "F=ma" },
            { "question_id": 2, "answer_text": "Gravity" }
        ]
    }
    ```
-   **Success Response** (200): `{ "success": true, "message": "Answers submitted..." }`

#### 5. Grade an Exam for a Student
-   **Endpoint**: `POST /api/exams/:examId/grade`
-   **Description**: Triggers the grading process for a student's submitted answers for an exam. It calculates the score and saves it to the `exam_results` table.
-   **Request Body**:
    ```json
    {
        "student_id": 123
    }
    ```
-   **Success Response** (200): `{ "message": "Exam graded...", "data": { "exam_id": 1, "student_id": 123, "score": 5, "total_points_possible": 10 } }`
