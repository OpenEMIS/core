<?php
// File paths
$apiEndpointsFile = 'storage/app/temp/apis.txt';
$failuresCsvFile = 'storage/logs/test_failures.csv';
$outputCsvFile = 'storage/logs/api_enpoints_test_report.csv';

// Read endpoints from the text file into an array, ignoring empty lines.
$endpoints = file($apiEndpointsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Build an associative array to hold failure data from the CSV.
// Key structure: $failures[<Method>][<Request URI>] = true;
$failures = [];
$firstFailures = [];
if (($handle = fopen($failuresCsvFile, 'r')) !== false) {
    // Skip header row.
    fgetcsv($handle);

    // Process each row of the CSV.
    while (($data = fgetcsv($handle)) !== false) {
        // CSV columns:
        // [0] Timestamp, [1] Method, [2] Request URI, [3] Headers,
        // [4] Request Data (PHP Code), [5] Response Status, [6] Response Body
        $method = isset($data[1]) ? trim($data[1]) : '';
        $requestUri = isset($data[2]) ? trim($data[2]) : '';

        if ($method && $requestUri) {
            $totalFailures++;

            // Aggregate count for this method and URI.
            if (!isset($failures[$method][$requestUri])) {
                $failures[$method][$requestUri] = 1;
            } else {
                $failures[$method][$requestUri]++;
            }

            // Save the first 5 failure rows.
            if (count($firstFailures) < 5) {
                $firstFailures[] = [
                    'method'     => $method,
                    'requestUri' => $requestUri,
                    'fullRow'    => $data,
                ];
            }
        }
    }
    fclose($handle);
} else {
    die("Could not open failures CSV file at: $failuresCsvFile");
}

// Output the total count and the first 5 failures.
echo "Total failures found: " . $totalFailures . "\n";
echo "First 5 failures:\n";
print_r($firstFailures);

// Define the HTTP methods to check.
$methods = ['GETJSON', 'POSTJSON', 'PUTJSON', 'DELETEJSON'];

// Open the output CSV file for writing.
if (($outHandle = fopen($outputCsvFile, 'w')) !== false) {
    // Write the header row.
    fputcsv($outHandle, array_merge(['endpoint'], $methods));

    // Process each endpoint from the text file.
    foreach ($endpoints as $endpoint) {
        $endpoint = trim($endpoint);
        // Normalize endpoint by removing the '/api' prefix if present.
        $normalized = (strpos($endpoint, '/api') === 0) ? substr($endpoint, 4) : $endpoint;

        $row = [$endpoint];
        foreach ($methods as $method) {
            $failureCount = 0;
            if (isset($failures[$method])) {
                if ($method === 'POSTJSON' || $method === 'GETJSON') {
                    // For POSTJSON, we do an exact match.
                    if (isset($failures[$method][$normalized])) {
                        $failureCount = $failures[$method][$normalized];
                    }
                } elseif ($method === 'PUTJSON' || $method === 'DELETEJSON') {
                    // For PUTJSON and DELETEJSON, check for endpoints with appended IDs.
                    foreach ($failures[$method] as $uri => $count) {
                        // If the CSV request URI starts with the normalized endpoint followed by a slash,
                        // count it as a match.
                        if (strpos($uri, $normalized . '/') === 0) {
                            $failureCount += $count;
                        }
                    }
                }
            }
            $row[] = ($failureCount > 0) ? 'error (' . $failureCount . ')' : 'success';
        }
        fputcsv($outHandle, $row);
    }

    fclose($outHandle);
    echo "Report generated successfully: $outputCsvFile" . PHP_EOL;
} else {
    die("Could not open output CSV file for writing: $outputCsvFile");
}
?>
