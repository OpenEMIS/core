#!/bin/bash

# Define file paths
API_LIST="storage/app/temp/apis.txt"
CSV_LOG="storage/app/temp/api_test_results.csv"

# Ensure the log directory exists
mkdir -p "$(dirname "$CSV_LOG")"

# Create CSV header if it doesn't exist
if [ ! -f "$CSV_LOG" ]; then
    echo "API,ModelName,Status,ExecutionTime(s)" > "$CSV_LOG"
fi

# Process each API path from the list
while IFS= read -r API_PATH; do
    echo "Processing $API_PATH..."

    START_TIME=$(date +%s)

    # Remove leading/trailing slashes and whitespace
    apiPath=$(echo "$API_PATH" | sed 's#^/##; s#/$##')

    # Get the last segment (resource name in kebab-case)
    resourceName=$(echo "$apiPath" | awk -F'/' '{print $NF}')

    # Convert resourceName from kebab-case to snake_case
    tableName=$(echo "$resourceName" | tr '-' '_')

    # Convert snake_case to StudlyCase (e.g., infrastructure_project_funding_sources → InfrastructureProjectFundingSources)
    # This sed command uppercases the first character and any character following an underscore, then removes underscores.
    modelName=$(echo "$tableName" | sed -r 's/(^|_)([a-z])/\U\2/g')

    echo "Detected model: $modelName"

    # Run the test command and capture output
    TEST_OUTPUT=$(php artisan test --filter="${modelName}ApiTest" 2>&1)
    TEST_EXIT_CODE=$?

    END_TIME=$(date +%s)
    EXEC_TIME=$((END_TIME - START_TIME))

    if [ $TEST_EXIT_CODE -eq 0 ]; then
        STATUS="Success"
    else
        STATUS="Failed"
    fi

    # Log the result to CSV
    echo "$API_PATH,$modelName,$STATUS,$EXEC_TIME" >> "$CSV_LOG"
    echo "Test result for $API_PATH ($modelName): $STATUS in ${EXEC_TIME}s"

done < "$API_LIST"

echo "All tests completed. Results saved in $CSV_LOG"
