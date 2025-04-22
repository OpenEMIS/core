#!/bin/bash

# Define the directory
API5_DIR="app/Models/Api5"

# Loop through files and rename them
for file in "$API5_DIR"/*___*.php; do
    if [[ -f "$file" ]]; then
        newFile="${file%___*}.php"
        echo "Renaming $file back to $newFile"
        mv "$file" "$newFile"
    fi
done
