# Transcript Service

This is a Node.js microservice for generating student transcripts. It is designed to connect to a core education system (like OpenEMIS) to fetch data and provide transcripts in JSON or PDF format.

## Features

-   Fetches student data from a core system via Direct Database Connection or REST API.
-   Provides an API endpoint to get transcripts: `GET /transcripts/:studentId`.
-   Supports both JSON and PDF output formats (`?format=pdf`).
-   Generates professional-looking PDF transcripts.
-   Logs all transcript generation activities.
-   Configurable via environment variables.

## Project Structure

```
transcript-service/
├── config/
│   └── config.js           # Configuration loader
├── src/
│   ├── controllers/
│   │   └── transcriptController.js # Handles API request logic
│   ├── routes/
│   │   └── transcriptRoutes.js   # Defines API routes
│   └── services/
│       ├── coreDataService.js  # Fetches data from the core system (DB or API)
│       ├── loggingService.js   # Winston logger
│       └── pdfService.js       # PDF generation logic
├── .env.example            # Example environment variables
├── .gitignore              # Files to ignore in version control
├── index.js                # Main server entrypoint
└── package.json            # Project dependencies and scripts
```

## Setup and Installation

### 1. Prerequisites

-   Node.js (v16 or later recommended)
-   NPM

### 2. Installation

1.  **Important:** This service is designed to be a standalone application. You should copy this `transcript-service` directory out of the main `openemis-core` repository and into its own new project folder and Git repository.

2.  **Navigate to the directory:**
    ```bash
    cd transcript-service
    ```

3.  **Install dependencies:**
    This command will download all the required libraries listed in `package.json`.
    ```bash
    npm install
    ```

### 3. Configuration

1.  **Create an environment file:**
    Copy the example `.env.example` file to a new file named `.env`. This file will hold your secret credentials and configuration, and it is ignored by Git.

    ```bash
    cp .env.example .env
    ```

2.  **Edit the `.env` file:**
    Open the `.env` file and fill in the required values for connecting to your core education system.

    ```dotenv
    # Server Configuration
    PORT=3000

    # Choose the connection method: 'DB' or 'API'
    CONNECTION_MODE=DB # or API

    # --- IF USING DATABASE (CONNECTION_MODE=DB) ---
    # Your core system's database details
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_USER=your_db_user
    DB_PASSWORD=your_db_password
    DB_NAME=openemis_core_db

    # --- IF USING API (CONNECTION_MODE=API) ---
    # Your core system's API endpoint details
    CORE_API_BASE_URL=http://your-core-system.com/api
    CORE_API_KEY=your-secret-api-key

    # Logging level (e.g., 'info', 'debug')
    LOG_LEVEL=info
    ```

### 4. Running the Service

Once installed and configured, you can start the service with:

```bash
npm start
```

The service will be available at `http://localhost:3000`. You should see a log message in your terminal confirming that the server has started.

## API Usage

### Get a Transcript

-   **Endpoint:** `GET /transcripts/:studentId`
-   **Description:** Retrieves the transcript for a given student.
-   **Parameters:**
    -   `studentId` (path): The ID of the student.
-   **Query Parameters:**
    -   `format` (optional): `json` (default) or `pdf`.

#### Example (JSON Response)

```bash
curl http://localhost:3000/transcripts/123
```

#### Example (PDF Response)

```bash
curl http://localhost:3000/transcripts/123?format=pdf --output transcript.pdf
```

## Connecting to the Core System

This service is flexible and can connect to your PHP-based core system in two ways, configured by the `CONNECTION_MODE` variable in your `.env` file.

### Method 1: Direct Database Connection (`CONNECTION_MODE=DB`)

This is the most direct method. The service connects to a read-only replica of your core system's database.

**Steps:**

1.  **Create a Read-Only User:** For security, create a dedicated, read-only MySQL user on your core system's database. Grant it `SELECT` privileges only on the necessary tables (e.g., `students`, `grades`, `courses`).
2.  **Configure `.env`:** Set `CONNECTION_MODE=DB` and fill in the `DB_HOST`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` variables.
3.  **Update Database Queries:** The queries in `src/services/coreDataService.js` are **examples**. You **must** review and update the table and column names to match your actual database schema.

### Method 2: REST API (`CONNECTION_MODE=API`)

If your core system exposes a REST API for fetching student data, this service can act as a client.

**Steps:**

1.  **Identify or Create an API Endpoint:** Ensure your core PHP system has an endpoint that can return transcript data for a student, ideally in JSON format. For example: `GET /api/students/:id/transcript`.
2.  **Secure the Endpoint:** Protect your API endpoint with an API key or another authentication mechanism.
3.  **Configure `.env`:** Set `CONNECTION_MODE=API` and fill in the `CORE_API_BASE_URL` and `CORE_API_KEY`.
4.  **Verify API Response:** The service assumes the API returns data in a specific structure. You may need to adjust the code in `getStudentDataFromApi` within `src/services/coreDataService.js` to match the actual response format from your API.
