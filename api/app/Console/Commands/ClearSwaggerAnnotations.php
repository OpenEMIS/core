<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ClearSwaggerAnnotations extends Command
{
    protected $signature = 'swagger:clear-swagger-annotations {apiPath}';
    protected $description = 'Clear Swagger annotations from models';

    public function handle()
    {
        $apiPath = trim($this->argument('apiPath'), '/'); // e.g. "api/v5/areas" or "areas"
        $apiPath = str_replace('_', '-', $apiPath); // Ensure kebab-case

        // Split path into segments and extract resource name (last segment)
        $segments = explode('/', $apiPath);
        $resourceName = end($segments);
        if (!str_starts_with($apiPath, 'api/v5/')) {
            $apiPath = 'api/v5/' . $resourceName;
        }
        // Table name is snake_case version of resourceName
        $tableName = str_replace('-', '_', $resourceName);
        // Model name in StudlyCase
        $modelName = Str::studly($tableName);

        $modelFile = app_path("Models/{$modelName}.php");

        if (!file_exists($modelFile)) {
            $this->warn("⚠️ Model file `{$modelFile}` does not exist. Skipping update.");
            return;
        } else {
            $this->warn("⚠️ Model file `{$modelFile}`   exist! Making update.");
        }


        // Read model file content and split into lines for processing.
        $modelContent = file_get_contents($modelFile);
        $lines = explode("\n", $modelContent);
        $filtereLines = [];
        $filteredLines = [];
        $insideSwaggerBlock = false;
        $insideSwaggerHelperBlock = false;
        foreach ($lines as $line) {
            if (str_contains($line, '/**')) {
                $insideSwaggerBlock = true;
                continue;
            }
            if ($insideSwaggerBlock && str_contains($line, '*/')) {
                $insideSwaggerBlock = false;
                continue;
            }
            if (!$insideSwaggerBlock) {
                $filtereLines[] = $line;
            }
        }
        foreach ($filtereLines as $line) {
            $trimmedLine = str_replace(["\t", "\r", "\x0B", "\0"], '', preg_replace('/\s+/', ' ', trim($line)));

            if (str_contains($trimmedLine, 'public function _swaggerUpdate() {}')) {
                continue;
            }
            if (str_contains($trimmedLine, 'public function _swaggerCreate() {}')) {
                continue;
            }
            if (str_contains($trimmedLine, 'public function _swaggerList() {}')) {
                continue;
            }
            if (str_contains($trimmedLine, 'public function _swaggerView() {}')) {
                continue;
            }
            if (str_contains($trimmedLine, 'public function _swaggerPath() {}')) {
                continue;
            }
            if (str_contains($trimmedLine, 'public function _swaggerDelete() {}')) {
                continue;
            }
            if (str_contains($line, '_swagger')) {
                $insideSwaggerHelperBlock = true;
                continue;
            }
            if ($insideSwaggerHelperBlock && str_contains($line, '}')) {
                $insideSwaggerHelperBlock = false;
                continue;
            }
            if (!$insideSwaggerHelperBlock) {
                $filteredLines[] = $line;
            }
        }

        $filteredText = implode("\n", $filteredLines);
        file_put_contents($modelFile, $filteredText);

        $this->info("✅ Cleared Swagger annotations in: {$modelFile}");


    }
}
