#!/usr/bin/env php
<?php
/**
 * This script runs "php artisan l5-swagger:generate" and, if it detects
 * errors related to merging Swagger annotations in one of the dummy _swagger* functions,
 * it disables the affected PHPDoc block by modifying its opening marker.
 * The script then retries until swagger generation succeeds or a non‑matching error occurs.
 */

$maxAttempts = 10;
$attempt = 0;

while ($attempt < $maxAttempts) {
    $attempt++;
    echo "Attempt $attempt:\n";

    // Run the swagger generate command and capture output and return code.
    // Redirect stderr to stdout.
    exec('php artisan l5-swagger:generate 2>&1', $output, $returnCode);
    $outputStr = implode("\n", $output);
    echo $outputStr . "\n";

    if ($returnCode === 0) {
        echo "Swagger generation succeeded.\n";
        break;
    } else {
        // Look for our specific error pattern.
        // Example error:
        // "Unable to merge @OA\Get() in \App\Models\AreaAdministratives->_swaggerList() in /var/www/html/core/core/api/app/Models/AreaAdministratives.php on line 71"
        if (preg_match('/Unable to merge @OA\\\\\w+\(\) in (\\\\App\\\\Models\\\\\w+)->(_swagger\w+)\(\) in (.+?) on line \d+/', $outputStr, $matches)) {
            $modelClass   = $matches[1];
            $functionName = $matches[2];
            $filePath     = $matches[3];
            $errorMessage = trim($outputStr);

            echo "Detected swagger error in file: $filePath, function: $functionName\n";
            modifyPhpDocInFile($filePath, $functionName, $errorMessage);
        } else {
            echo "Error not recognized or not related to _swagger functions. Aborting.\n";
            break;
        }
    }

    // Clear output array for next iteration.
    $output = [];
    // Optional: wait a moment before retrying.
    sleep(1);
}

/**
 * Modify the PHPDoc block preceding the given dummy function by "disabling" it.
 *
 * This function reads the file line by line, finds the target dummy function (e.g. _swaggerList),
 * then goes backward until it finds the PHPDoc block start marker (/**). Once found, it replaces
 * the opening marker with /* (disabling the annotation) and inserts a warning comment.
 *
 * @param string $filePath     The path of the file to modify.
 * @param string $functionName The dummy function name (e.g. _swaggerList).
 * @param string $errorMessage The full error message detected.
 */
function modifyPhpDocInFile($filePath, $functionName, $errorMessage)
{
    echo "Modifying file: $filePath for function: $functionName\n";
    $contents = file_get_contents($filePath);
    if ($contents === false) {
        echo "Failed to read file: $filePath\n";
        return;
    }

    // Split file content into lines.
    $lines = explode("\n", $contents);
    $targetIndex = null;

    // Find the line containing the target function definition.
    foreach ($lines as $index => $line) {
        // Normalize spaces and remove hidden characters.
        $trimLine   = trim($line);
        $trimmLine  = preg_replace('/\s+/', ' ', $trimLine);
        $trimmeLine = preg_replace('/\s+/', ' ', $trimmLine);
        $trimmedLine = str_replace(["\t", "\r", "\x0B", "\0"], '', $trimmeLine);
        if (strpos($trimmedLine, 'public function ' . $functionName . '(') !== false) {
            $targetIndex = $index;
            break;
        }
    }

    if ($targetIndex === null) {
        echo "Function $functionName not found in $filePath\n";
        return;
    }

    // Walk backwards from the function line to find the PHPDoc block start.
    $docStartIndex = null;
    for ($i = $targetIndex - 1; $i >= 0; $i--) {
        $trimLine   = trim($lines[$i]);
        $trimmLine  = preg_replace('/\s+/', ' ', $trimLine);
        $trimmeLine = preg_replace('/\s+/', ' ', $trimmLine);
        $trimmedLine = str_replace(["\t", "\r", "\x0B", "\0"], '', $trimmeLine);
        // Check if the line starts with '/**'
        if (strpos($trimmedLine, '/**') === 0) {
            $docStartIndex = $i;
            break;
        }
    }

    if ($docStartIndex === null) {
        echo "No PHPDoc block found for function $functionName in $filePath\n";
        return;
    }

    // Modify the PHPDoc block: change '/**' to '/*'
    $lines[$docStartIndex] = str_replace('/**', '/*', $lines[$docStartIndex]);

    // Insert a warning comment as a new line right after the PHPDoc start.
    $warning = " * // should be reviewed, the error: $errorMessage occurred, probably the api path is already registered by another class";
    array_splice($lines, $docStartIndex + 1, 0, $warning);

    // Reassemble and write the file.
    $newContents = implode("\n", $lines);
    file_put_contents($filePath, $newContents);
    echo "Modified file: $filePath\n";
}


