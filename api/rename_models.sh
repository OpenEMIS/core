#!/bin/bash

# Define paths
API_LIST="storage/app/temp/apis.txt"
API5_DIR="app/Models/Api5"
LOG_FILE="storage/app/temp/cleanup_log.txt"

# Ensure the log directory exists
mkdir -p storage/app/temp

# Start log file
echo "🚀 Starting cleanup process - $(date)" > "$LOG_FILE"

# Function to convert API path to model name
convert_to_model_name() {
    local apiPath=$1

    # Trim trailing slashes
    apiPath=$(echo "$apiPath" | sed 's:/*$::')

    # Replace underscores with hyphens (to normalize input)
    apiPath=${apiPath//_/-}

    # Split into segments
    IFS='/' read -r -a segments <<< "$apiPath"
    local resourceName="${segments[-1]}"

    # Normalize to api/v5/resource if needed
    if [[ ! "$apiPath" == api/v5/* ]]; then
        apiPath="api/v5/$resourceName"
    fi

    # Convert to snake_case
    local tableName="${resourceName//-/_}"

    # Convert to StudlyCase model name
    local modelName
    modelName=$(echo "$tableName" | awk -F'_' '{for (i=1; i<=NF; i++) printf toupper(substr($i,1,1)) tolower(substr($i,2)); print ""}')

    # Output only the model name
    echo "$modelName"
}


# Read API paths from file and collect model names
valid_models=()
while IFS= read -r API_PATH; do
    modelName=$(convert_to_model_name "$API_PATH")
    valid_models+=("$modelName")
done < "$API_LIST"

# Scan Api5 directory and rename models not in the valid list
first_file_processed=false
for modelFile in "$API5_DIR"/*.php; do
    if [[ -f "$modelFile" ]]; then
        modelName=$(basename "$modelFile" .php)
        echo "Checking model file: $modelFile" | tee -a "$LOG_FILE"
        echo "Model Name: $modelName" | tee -a "$LOG_FILE"

       if [[ ! " ${valid_models[@]} " =~ " ${modelName} " ]]; then
           newModelFile="$(dirname "$modelFile")/___$(basename "$modelFile")"
           echo "Would rename model: $modelFile to $newModelFile" | tee -a "$LOG_FILE"
#           first_file_processed=true
           # Uncomment the next line to actually rename the file
            mv "$modelFile" "$newModelFile"
       else
           echo "Would keep model: $modelFile" | tee -a "$LOG_FILE"
#           first_file_processed=true
       fi

        # Stop after processing the first file

        if [[ "$first_file_processed" == true ]]; then
            break
        fi
    fi
done

# Final message
echo "✅ Debug process completed - $(date)" | tee -a "$LOG_FILE"


##!/bin/bash
#
## Define the directory
#API5_DIR="app/Models/Api5"
#
## Loop through files and rename them
#for file in "$API5_DIR"/*___*.php; do
#    if [[ -f "$file" ]]; then
#        newFile="${file%___*}.php"
#        echo "Renaming $file back to $newFile"
#        mv "$file" "$newFile"
#    fi
#done
