# Transcript Service (Enhanced)

This is a Node.js microservice for generating and managing student transcripts. It is designed to connect to a core education system to fetch data, provide transcripts in JSON or PDF format, and offer access to generation logs.

## Features

-   Fetches student data from a core system via Direct Database Connection or REST API.
-   Provides an API endpoint to get transcripts: `GET /transcripts/:studentId`.
-   Supports both JSON and PDF output formats (`?format=pdf`).
-   Provides an API endpoint to view generation logs: `GET /transcripts/logs`.
-   Generates professional-looking PDF transcripts using `pdfkit`.
-   Logs all transcript generation events to a daily rotating file using `winston`.
-   All configuration is managed through a `.env` file.
-   Includes robust error handling (404, 500) and input validation.

## Project Structure

```
transcript-service/
├── config/
│   └── config.js           # Configuration loader
├── output/                 # (Created automatically) For log files
├── src/
│   ├── controllers/
│   │   └── transcriptController.js
│   ├── routes/
│   │   └── transcriptRoutes.js
│   └── services/
│       ├── coreDataService.js
│       ├── logReaderService.js
│       ├── loggingService.js
│       └── pdfService.js
├── .env.example            # Example environment variables
├── index.js                # Main server entrypoint
└── package.json            # Project dependencies
```

## Setup and Installation

### 1. Prerequisites

-   Node.js (v16 or later recommended)
-   NPM

### 2. Installation

1.  **Clone or copy this project** into its own dedicated directory.

2.  **Navigate into the project directory:**
    ```bash
    cd transcript-service
    ```

3.  **Install dependencies:**
    This command downloads all required libraries defined in `package.json`.
    ```bash
    npm install
    ```

### 3. Configuration

1.  **Create an environment file** by copying the example file. This file is ignored by Git and should contain your secret credentials.
    ```bash
    cp .env.example .env
    ```

2.  **Edit the `.env` file** with your specific settings.

    ```dotenv
    # Server Port
    PORT=3000

    # Connection method: 'DB' or 'API'
    CONNECTION_MODE=DB

    # Database Settings (for DB mode)
    CORE_DB_HOST=127.0.0.1
    CORE_DB_USER=your_db_user
    CORE_DB_PASSWORD=your_db_password
    CORE_DB_NAME=your_db_name

    # API Settings (for API mode)
    CORE_API_URL=http://your-api-url.com/api
    CORE_API_KEY=your-secret-api-key

    # Logging and Output Directory
    LOG_LEVEL=info
    OUTPUT_DIR=./output
    ```

### 4. Running the Service

Start the server using the `npm start` script:
```bash
npm start
```
The service will be available at `http://localhost:3000`. Check the console for startup log messages.

## API Usage

### Get a Student Transcript (JSON)

-   **Endpoint:** `GET /transcripts/:studentId`
-   **Description:** Retrieves the transcript for a given student.
-   **Returns:** A JSON object with the student's data.

#### Example Request:
```bash
curl http://localhost:3000/transcripts/123
```

### Get a Transcript as a PDF

-   **Endpoint:** `GET /transcripts/:studentId?format=pdf`
-   **Description:** Retrieves the transcript for a given student as a PDF file.
-   **Returns:** A PDF file stream.

#### Example Request:
```bash
# This command will save the output to a file named 'transcript-123.pdf'
curl http://localhost:3000/transcripts/123?format=pdf --output transcript-123.pdf
```

### Get Transcript Generation Logs

-   **Endpoint:** `GET /transcripts/logs`
-   **Description:** Retrieves a list of all transcript generation events that have been logged by the service.
-   **Returns:** A JSON array of log objects, with the most recent events first.

#### Example Request:
```bash
curl http://localhost:3000/transcripts/logs
```
