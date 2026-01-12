#!/bin/bash

# Define paths
API_LIST="storage/app/temp/apis.txt"
CSV_LOG="storage/app/temp/api_batch_results.csv"
FULL_LOG="storage/app/temp/api_batch_log.txt"

# ✅ Ensure storage/temp exists before writing logs
mkdir -p storage/app/temp

# Create CSV header if it doesn't exist
if [ ! -f "$CSV_LOG" ]; then
    echo "API,Status,Model,Factory,Tests, Execution Time (s)" > "$CSV_LOG"
fi

# Start log file
echo "🚀 Running API Batch Process - $(date)" > "$FULL_LOG"

# Read API paths from file
while IFS= read -r API_PATH; do
    echo "⚡ Processing $API_PATH..." | tee -a "$FULL_LOG"

    # ✅ Start execution timer
    START_TIME=$(date +%s)

    # Run Laravel command and capture output
    OUTPUT=$(stdbuf -o0 php artisan make:new-api "$API_PATH" 2>&1)

    # ✅ End execution timer
    END_TIME=$(date +%s)
    EXEC_TIME=$((END_TIME - START_TIME))  # Calculate elapsed time in seconds

    # Determine status based on output
    if [[ "$OUTPUT" == *"API setup complete"* ]]; then
        STATUS="Success"
    else
        STATUS="Failed"
    fi

    # Check for specific messages in the output
    MODEL_STATUS=$(echo "$OUTPUT" | grep -q "Model successfully created" && echo "Created" || echo "Skipped")
    FACTORY_STATUS=$(echo "$OUTPUT" | grep -q "Factory successfully created" && echo "Created" || echo "Skipped")
    TEST_STATUS=$(echo "$OUTPUT" | grep -q "Test cases generated" && echo "Created" || echo "Skipped")

        # ✅ Append results to CSV (including execution time)
    echo "$API_PATH,$STATUS,$MODEL_STATUS,$FACTORY_STATUS,$TEST_STATUS,$EXEC_TIME" >> "$CSV_LOG"

    # ✅ Append full output to log file
    echo "$OUTPUT" >> "$FULL_LOG"
    echo "Execution Time: ${EXEC_TIME}s" >> "$FULL_LOG"
    echo "----------------------------------------" >> "$FULL_LOG"

done < "$API_LIST"

# Final message
echo "✅ API Batch Completed - Results saved in $CSV_LOG"
