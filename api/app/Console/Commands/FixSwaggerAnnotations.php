<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class FixSwaggerAnnotations extends Command
{
    protected $signature = 'swagger:fix-swagger-annotations {apiPath}';
    protected $description = 'Fix Swagger annotations from models';

    public function handle()
    {
        // Clean and standardize the API path
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

        // Update model properties (timestamps, fillable, etc.)
        $this->info("🎉 $modelName, $tableName start!");
        $this->updateModelProperties($modelName, $tableName);


        $this->info("🎉 API setup complete!");
    }

    /**
     * Updates the model file with properties such as $fillable, $dates, and $timestamps.
     * Also inserts Swagger PHPDoc comments later.
     */
    private function updateModelProperties($modelName, $tableName)
    {
        // Model now resides in Models/Api5 folder
        $modelFile = app_path("Models/{$modelName}.php");

        if (!file_exists($modelFile)) {
            $this->warn("⚠️ Model file `{$modelFile}` does not exist. Skipping update.");
            return;
        }else{
            $this->warn("⚠️ Model file `{$modelFile}`   exist! Making update.");
        }

        $columns = $this->getTableColumns($tableName);
        if (empty($columns)) {
            $this->warn("⚠️ Table `{$tableName}` has no columns. Skipping update.");
            return;
        }

        // Prepare fillable columns and ensure foreign keys ending in '_id' are included
        $timestamps = ['created_at', 'updated_at', 'deleted_at'];
        $fillableColumns = array_keys($columns);
        foreach ($columns as $column => $details) {
            if (str_ends_with($column, '_id')) {
                $this->info("🔍 Ensuring foreign key `{$column}` is fillable...");
                $fillableColumns[] = $column;
            }
        }
        $fillableColumns = array_unique(array_filter($fillableColumns, fn($column) => !in_array($column, $timestamps)));

        // Read model file content and split into lines for processing.
        $modelContent = file_get_contents($modelFile);
        $lines = explode("\n", $modelContent);



        $generateComplexSwagger = true;
        // Generate and insert Swagger PHPDoc annotations.
        if (!preg_match('/\$primaryKey\s*=\s*\[([^\]]+)\]/', $modelContent, $matches)) {
            $this->info("No composite primary key found in {$modelName}. Skipping Complex Swagger.");
            $generateComplexSwagger = false;
        }
        if($generateComplexSwagger){
            $keysRaw = $matches[1];
            $keysArray = array_map(function ($key) {
                return trim($key, " '\"\t\n");
            }, explode(',', $keysRaw));

            if (count($keysArray) < 1) {
                $this->info("Primary key is not composite in {$modelName}. Skipping Complex Swagger.");
                $generateComplexSwagger = false;
            }
        }
        $swaggerDocListCreate = $this->generateSwaggerDoc($modelName, $tableName, $columns);
        if($generateComplexSwagger){
            $swaggerDocViewUpdateDelete = $this->generateComplexSwaggerDoc($modelName, $tableName, $keysArray);
        }else{
            $swaggerDocViewUpdateDelete = $this->generateSimpleSwaggerDoc($modelName, $tableName, $columns);
        }
        $swaggerDoc = $swaggerDocListCreate . "\n\n" . $swaggerDocViewUpdateDelete;
        $this->ensureModelHasMethod($modelFile);
        $this->insertSwaggerDocInModel($modelFile, $swaggerDoc);
    }

    /**
     * Ensures that the model file has at least one public method (_swaggerHelper)
     * to attach the Swagger annotations.
     */
    private function ensureModelHasMethod($modelFile)
    {
        $content = file_get_contents($modelFile);

        $lines = explode("\n", $content);
        $insertIndex = null;
        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*(public|private|protected)\s+function\s+/', $line)) {
                $insertIndex = $index;
                break;
            }
        }
        if ($insertIndex === null) {
            $this->info("🔍 No function found in `{$modelFile}`. Adding `_swaggerHelper()`.");
            $content = preg_replace('/}\s*$/', "\n\npublic function _swaggerHelper(){}\n\n}", $content);
            file_put_contents($modelFile, $content);
        }
    }

    /**
     * Converts snake_case to kebab-case.
     */
    private function toKebabCase($string)
    {
        return str_replace('_', '-', strtolower($string));
    }

    /**
     * Generates OpenAPI (Swagger) documentation for the given model.
     * Also adds five optional GET parameters:
     *   - limit (number)
     *   - page (number)
     *   - orderby (string)
     *   - order (string: asc or desc)
     *   - _fields (string)
     */
    private function generateSwaggerDoc($modelName, $tableName, $columns)
    {
        $resourcePath = $this->toKebabCase($tableName);
        // Generate JSON schema for model properties.
        $jsonSchema = $this->generateJsonSchema($columns);
        $jsonSchemaForItems   = $this->indentText($jsonSchema, 26);
        $jsonSchemaForContent = $this->indentText($jsonSchema, 21);

        // Swagger documentation for the common path.
        $swaggerPathDoc = <<<EOT
/**
 * @OA\PathItem(
 *     path="/api/v5/{$resourcePath}"
 * )
 */
public function _swaggerPath() {}
EOT;

        // Swagger documentation for GET list with additional query parameters.
        $swaggerListDoc = <<<EOT
/**
 * @OA\Get(
 *     path="/api/v5/{$resourcePath}",
 *     summary="Get list of {$modelName}",
 *     tags={"{$modelName}"},
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         required=false,
 *         description="Maximum number of results to return",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number for paginated results",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="orderby",
 *         in="query",
 *         required=false,
 *         description="Field to order results by",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="order",
 *         in="query",
 *         required=false,
 *         description="Order direction: asc or desc",
 *         @OA\Schema(type="string", enum={"asc", "desc"})
 *     ),
 *     @OA\Parameter(
 *         name="_fields",
 *         in="query",
 *         required=false,
 *         description="Comma-separated list of fields to include in response",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Successful."
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
{$jsonSchemaForItems}
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function _swaggerList() {}
EOT;

        // Swagger documentation for POST (create).
        $swaggerCreateDoc = <<<EOT
/**
 * @OA\Post(
 *     path="/api/v5/{$resourcePath}",
 *     summary="Create a new {$modelName}",
 *     tags={"{$modelName}"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
{$jsonSchemaForContent}
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function _swaggerCreate() {}
EOT;



        // Concatenate all dummy functions with two newlines between each.
        return $swaggerPathDoc . "\n\n" .
            $swaggerListDoc . "\n\n" .
            $swaggerCreateDoc . "\n\n";
    }

    private function generateComplexSwaggerDoc($modelName, $tableName, $keys)

    {
        // Build URL segment by concatenating each key with its placeholder.
        $urlSegment = '';
        foreach ($keys as $key) {
            $urlSegment .= $key . '/{' . $key . '}/';
        }
        $urlSegment = rtrim($urlSegment, '/');

        // GET endpoint
        $swagger = "/**\n";
        $swagger .= " * @OA\\Get(\n";
        $swagger .= " *     path=\"/api/v5/{$tableName}/{$urlSegment}\",\n";
        $swagger .= " *     summary=\"Get {$modelName} record by composite key\",\n";
        $swagger .= " *     tags={\"{$modelName}\"},\n";
        foreach ($keys as $key) {
            $swagger .= " *     @OA\\Parameter(\n";
            $swagger .= " *         name=\"{$key}\",\n";
            $swagger .= " *         in=\"path\",\n";
            $swagger .= " *         required=true,\n";
            $swagger .= " *         description=\"{$key}\",\n";
            $swagger .= " *         @OA\\Schema(type=\"string\")\n";
            $swagger .= " *     ),\n";
        }
        $swagger .= " *     @OA\\Response(\n";
        $swagger .= " *         response=200,\n";
        $swagger .= " *         description=\"Record found\"\n";
        $swagger .= " *     ),\n";
        $swagger .= " *     @OA\\Response(\n";
        $swagger .= " *         response=404,\n";
        $swagger .= " *         description=\"Record not found\"\n";
        $swagger .= " *     )\n";
        $swagger .= " * )\n";
        $swagger .= " */\n";
        $swagger .= "public function _swaggerView() {}\n\n";

        // PUT endpoint
        $swagger .= "/**\n";
        $swagger .= " * @OA\\Put(\n";
        $swagger .= " *     path=\"/api/v5/{$tableName}/{$urlSegment}\",\n";
        $swagger .= " *     summary=\"Update {$modelName} record by composite key\",\n";
        $swagger .= " *     tags={\"{$modelName}\"},\n";
        foreach ($keys as $key) {
            $swagger .= " *     @OA\\Parameter(\n";
            $swagger .= " *         name=\"{$key}\",\n";
            $swagger .= " *         in=\"path\",\n";
            $swagger .= " *         required=true,\n";
            $swagger .= " *         description=\"{$key}\",\n";
            $swagger .= " *         @OA\\Schema(type=\"string\")\n";
            $swagger .= " *     ),\n";
        }
        $swagger .= " *     @OA\\RequestBody(\n";
        $swagger .= " *         required=true,\n";
        $swagger .= " *         @OA\\JsonContent(\n";
        $swagger .= " *             type=\"object\",\n";
        $swagger .= " *             ";
        $swagger .= " *         )\n";
        $swagger .= " *     ),\n";
        $swagger .= " *     @OA\\Response(\n";
        $swagger .= " *         response=200,\n";
        $swagger .= " *         description=\"Record updated successfully\"\n";
        $swagger .= " *     ),\n";
        $swagger .= " *     @OA\\Response(\n";
        $swagger .= " *         response=400,\n";
        $swagger .= " *         description=\"Invalid data provided\"\n";
        $swagger .= " *     ),\n";
        $swagger .= " *     @OA\\Response(\n";
        $swagger .= " *         response=404,\n";
        $swagger .= " *         description=\"Record not found\"\n";
        $swagger .= " *     )\n";
        $swagger .= " * )\n";
        $swagger .= " */\n";
        $swagger .= "public function _swaggerUpdate() {}\n\n";

        // DELETE endpoint
        $swagger .= "/**\n";
        $swagger .= " * @OA\\Delete(\n";
        $swagger .= " *     path=\"/api/v5/{$tableName}/{$urlSegment}\",\n";
        $swagger .= " *     summary=\"Delete {$modelName} record by composite key\",\n";
        $swagger .= " *     tags={\"{$modelName}\"},\n";
        foreach ($keys as $key) {
            $swagger .= " *     @OA\\Parameter(\n";
            $swagger .= " *         name=\"{$key}\",\n";
            $swagger .= " *         in=\"path\",\n";
            $swagger .= " *         required=true,\n";
            $swagger .= " *         description=\"{$key}\",\n";
            $swagger .= " *         @OA\\Schema(type=\"string\")\n";
            $swagger .= " *     ),\n";
        }
        $swagger .= " *     @OA\\Response(\n";
        $swagger .= " *         response=204,\n";
        $swagger .= " *         description=\"Record deleted successfully\"\n";
        $swagger .= " *     ),\n";
        $swagger .= " *     @OA\\Response(\n";
        $swagger .= " *         response=404,\n";
        $swagger .= " *         description=\"Record not found\"\n";
        $swagger .= " *     )\n";
        $swagger .= " * )\n";
        $swagger .= " */\n";
        $swagger .= "public function _swaggerDelete() {}\n";

        return $swagger;
    }
private function generateSimpleSwaggerDoc($modelName, $tableName, $columns)
    {
        $resourcePath = $this->toKebabCase($tableName);
        // Generate JSON schema for model properties.
        $jsonSchema = $this->generateJsonSchema($columns);
        $jsonSchemaForContent = $this->indentText($jsonSchema, 21);

        // Swagger documentation for GET by ID.
        $swaggerViewDoc = <<<EOT
/**
 * @OA\Get(
 *     path="/api/v5/{$resourcePath}/{id}",
 *     summary="Get {$modelName} by ID",
 *     tags={"{$modelName}"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the {$modelName}",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerView() {}
EOT;

        // Swagger documentation for PUT (update).
        $swaggerUpdateDoc = <<<EOT
/**
 * @OA\Put(
 *     path="/api/v5/{$resourcePath}/{id}",
 *     summary="Update {$modelName}",
 *     tags={"{$modelName}"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the {$modelName}",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
{$jsonSchemaForContent}
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Updated successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerUpdate() {}
EOT;

        // Swagger documentation for DELETE.
        $swaggerDeleteDoc = <<<EOT
/**
 * @OA\Delete(
 *     path="/api/v5/{$resourcePath}/{id}",
 *     summary="Delete {$modelName}",
 *     tags={"{$modelName}"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the {$modelName}",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerDelete() {}
EOT;

        // Concatenate all dummy functions with two newlines between each.
        return $swaggerViewDoc . "\n\n" .
            $swaggerUpdateDoc . "\n\n" .
            $swaggerDeleteDoc;
    }

    /**
     * Inserts the generated Swagger PHPDoc into the model file,
     * just before the first public method.
     */
    private function insertSwaggerDocInModel($modelFile, $swaggerDoc)
    {
        $content = file_get_contents($modelFile);
        if (str_contains($content, "@OA\PathItem")) {
            $this->info("✅ Swagger PHPDoc already exists in `{$modelFile}`. Skipping...");
            return;
        }
        $lines = explode("\n", $content);
        $insertIndex = null;
        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*(public|private|protected)\s+function\s+/', $line)) {
                $insertIndex = $index;
                break;
            }
        }
        if ($insertIndex !== null) {
            array_splice($lines, $insertIndex, 0, explode("\n", $swaggerDoc));
            $newContent = implode("\n", $lines);
        } else {
            $newContent = $content . "\n" . $swaggerDoc . "\nprivate function emptyFunction() { return; }\n";
        }
        $newContent = $this->cleanEmptyLines($newContent);
        file_put_contents($modelFile, $newContent);
        $this->info("✅ Swagger PHPDoc added to `{$modelFile}`.");
    }

    /**
     * Generate JSON Schema for OpenAPI based on table columns.
     */
    private function generateJsonSchema($columns)
    {
        $schema = [];
        foreach ($columns as $column => $details) {
            $schema[] = $this->mapColumnToOAProperty($column, $details);
        }
        if (!empty($schema)) {
            $last = array_pop($schema);
            $last = rtrim($last, ",");
            $schema[] = $last;
        }
        return implode("\n", $schema);
    }

    /**
     * Map a database column to an OpenAPI property annotation.
     */
    private function mapColumnToOAProperty($column, $details)
    {
        $type = match (true) {
            str_contains($details['type'], 'int') => 'integer',
            str_contains($details['type'], 'decimal') || str_contains($details['type'], 'float') => 'number',
            str_contains($details['type'], 'varchar') || str_contains($details['type'], 'text') => 'string',
            default => 'string',
        };

        $format = '';
        if (str_contains($details['type'], 'datetime') || str_contains($details['type'], 'timestamp')) {
            $format = ', format="date-time"';
        } elseif (str_contains($details['type'], 'date')) {
            $format = ', format="date"';
        }

        return "@OA\Property(property=\"{$column}\", type=\"{$type}\"{$format}, example=null),";
    }

    /**
     * Indents text with a given number of spaces.
     */
    private function indentText($text, $spaces)
    {
        $indent = str_repeat(' ', $spaces);
        return implode("\n", array_map(fn($line) => $indent . $line, explode("\n", $text)));
    }

    /**
     * Returns an array of column details for the given table.
     */
    private function getTableColumns($tableName): array
    {
        $columns = \DB::select("SHOW COLUMNS FROM {$tableName}");
        $schema = [];
        foreach ($columns as $column) {
            $schema[$column->Field] = [
                'type' => $column->Type,
                'nullable' => $column->Null === 'YES',
                'key' => $column->Key,
                'is_primary' => $column->Key === 'PRI',
            ];
        }
        // Fetch foreign key constraints
        $foreignKeys = \DB::select("
        SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = '{$tableName}'
        AND TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
        foreach ($foreignKeys as $fk) {
            $schema[$fk->COLUMN_NAME]['foreign_key'] = [
                'table' => $fk->REFERENCED_TABLE_NAME,
                'column' => $fk->REFERENCED_COLUMN_NAME,
            ];
        }
        return $schema;
    }

    /**
     * @param string $newContent
     * @return string
     */
    private function cleanEmptyLines(string $newContent): string
    {
        $allLines = explode("\n", $newContent);
        $finalLines = [];
        $emptyLineCount = 0;
        foreach ($allLines as $line) {
            // Normalize the line as provided
            $trimLine = trim($line);
            $trimmLine = preg_replace('/\s+/', ' ', $trimLine);
            $trimmeLine = preg_replace('/\s+/', ' ', $trimmLine);
            $trimmedLine = str_replace(["\t", "\r", "\x0B", "\0"], '', $trimmeLine);

            if ($trimmedLine === '') {
                $emptyLineCount++;
                if ($emptyLineCount <= 2) {
                    $finalLines[] = $line;
                }
            } else {
                $emptyLineCount = 0;
                $finalLines[] = $line;
            }
        }
        $newContent = implode("\n", $finalLines);
        return $newContent;
    }
}
