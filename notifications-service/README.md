# Notifications Service

## Overview

This is a Node.js microservice designed to handle all outgoing communications for the Ethiopian Ministry of Education's digital education system. It receives requests from other services to send notifications to users via multiple channels, such as Email and SMS.

The service is responsible for fetching user contact details from the core system, dispatching messages through third-party providers (e.g., an SMTP server for email, Twilio for SMS), and logging the outcome of every notification attempt.

## Features

-   **Multi-channel Notifications**: Supports sending notifications via Email and SMS.
-   **Centralized Logic**: Provides a single endpoint (`/api/send`) for all other microservices to use.
-   **Third-party Integration**: Uses `nodemailer` for email and `twilio` for SMS, with configurations managed via environment variables.
-   **Auditing**: Logs every sent or failed notification attempt to its own database table for easy tracking and debugging.
-   **Data-driven**: Fetches user contact information from the core system at send time, so it doesn't store user data itself.

## Prerequisites

-   Node.js (v14 or higher)
-   NPM
-   A running MySQL database server.
-   Credentials for an SMTP server (e.g., SendGrid, Mailgun, or a private server).
-   A Twilio account with an Account SID, Auth Token, and a provisioned phone number.

## Setup and Installation

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd notifications-service
    ```

2.  **Install dependencies:**
    ```bash
    npm install
    ```

3.  **Database Setup:**
    This service requires its own database to log notification attempts. Connect to your MySQL server and run the DDL statement in `config/schema.sql` to create the database and the `notification_logs` table.

4.  **Configure environment variables:**
    Create a `.env` file in the root of the project by copying the example file:
    ```bash
    cp .env.example .env
    ```
    Now, edit the `.env` file with your specific configuration. **This is a critical step.** You must provide the credentials for the database, the core system API, your SMTP server, and your Twilio account.

## Running the Service

-   **Production mode:**
    ```bash
    npm start
    ```

-   **Development mode (with auto-restarting):**
    ```bash
    npm run dev
    ```

The service will start on the port specified in your `.env` file (default: 3004).

## API Usage

### Send a Notification

This service exposes a single primary endpoint to handle all notification requests.

-   **Endpoint**: `POST /api/send`
-   **Description**: Processes a request to send a notification to a specific user. The service will look up the user's contact details and dispatch the message to the specified channel(s).
-   **Request Body** (JSON):
    ```json
    {
        "userId": 123,
        "channel": "ALL",
        "subject": "Your Exam Results Are In!",
        "message": "Hello! Your results for the Mid-term Physics exam are now available on the student portal."
    }
    ```
    **Body Parameters:**
    -   `userId` (required): The ID of the user in the core system.
    -   `message` (required): The content of the notification. This will be used as the body for both SMS and Email.
    -   `channel` (optional, defaults to `'ALL'`): The channel to send through. Options are `'EMAIL'`, `'SMS'`, or `'ALL'`.
    -   `subject` (optional, defaults to `'Notification from OpenEMIS'`): The subject line for email notifications.

-   **Success Response** (200 OK):
    The response is an array detailing the outcome for each channel that was attempted.
    ```json
    {
        "message": "Notification request processed.",
        "data": [
            {
                "channel": "EMAIL",
                "success": true
            },
            {
                "channel": "SMS",
                "success": false,
                "error": "Failed to send SMS."
            }
        ]
    }
    ```
