# Transcript Service

## Overview

This is a Node.js microservice designed to generate student transcripts for the Ethiopian Ministry of Education's digital education system. It can connect to the core student information system either via a direct database connection or a RESTful API to fetch data.

The service provides an endpoint to retrieve a student's transcript in either JSON or PDF format.

## Features

-   **Dual Data Source Mode**: Connect to the core system via MySQL (`db`) or a REST API (`api`).
-   **Multiple Formats**: Generate transcripts in JSON for data consumption or PDF for official records.
-   **Scalable Architecture**: Built with a modular structure (services, controllers, routes) for easy maintenance and expansion.
-   **Logging**: All transcript generation requests and errors are logged using Winston.

## Prerequisites

-   Node.js (v14 or higher)
-   NPM

## Setup and Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd transcript-service
    ```

2.  **Install dependencies:**
    ```bash
    npm install
    ```

3.  **Configure environment variables:**
    Create a `.env` file in the root of the project by copying the example file:
    ```bash
    cp .env.example .env
    ```
    Now, edit the `.env` file with your specific configuration.

    ### Environment Variables

    -   `TRANSCRIPT_SERVICE_PORT`: The port the service will run on (e.g., `3001`).
    -   `CORE_DATA_MODE`: The method to fetch student data.
        -   Set to `api` to use the core system's API.
        -   Set to `db` to use a direct database connection.
    -   `CORE_DB_HOST`, `CORE_DB_USER`, `CORE_DB_PASSWORD`, `CORE_DB_NAME`: Database credentials (if `CORE_DATA_MODE=db`).
    -   `CORE_API_BASE_URL`, `CORE_API_KEY`: Core API details (if `CORE_DATA_MODE=api`).
    -   `LOG_LEVEL`: The level for logging (e.g., `info`, `debug`).

## Running the Service

-   **Production mode:**
    ```bash
    npm start
    ```

-   **Development mode (with auto-restarting):**
    ```bash
    npm run dev
    ```

The service will start on the port specified in your `.env` file.

## API Usage

### Get a Student Transcript

-   **Endpoint**: `GET /api/transcripts/:studentId`
-   **Description**: Retrieves the transcript for a specific student.
-   **Parameters**:
    -   `studentId` (path parameter): The ID of the student.
    -   `format` (query parameter, optional): `json` or `pdf`. Defaults to `json`.

#### Example using cURL

**Get JSON format:**

```bash
curl http://localhost:3001/api/transcripts/12345
```

**Get PDF format:**

```bash
curl http://localhost:3001/api/transcripts/12345?format=pdf -o transcript.pdf
```

This will save the generated transcript as `transcript.pdf` in your current directory.
