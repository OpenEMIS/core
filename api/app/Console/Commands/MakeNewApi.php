<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MakeNewApi extends Command
{
    protected $signature = 'make:new-api {apiPath}';
    protected $description = 'Generates API resources (model, migration if needed, tests, factory)';

    public function handle()
    {
        $apiPath = trim($this->argument('apiPath'), '/'); // Example: api/v5/areas or areas
        $apiPath = str_replace('_', '-', $apiPath); // Convert to kebab-case
        if (!str_starts_with($apiPath, 'api/v5/')) {
            $apiPath = 'api/v5/' . $apiPath;
        }
        $segments = explode('/', $apiPath);
        $resourceName = end($segments); // Extract the last segment (e.g., 'areas')

        $tableName = str_replace('-', '_', $resourceName); // Convert to snake_case anyway
        $modelName = Str::studly($tableName); // Convert to PascalCase (Areas → Areas)
        $this->updateModelProperties($modelName, $tableName);
        $testFile = base_path("tests/Feature/{$modelName}ApiTest.php");
        $factoryFile = base_path("database/factories/{$modelName}Factory.php");
        $controllerFile = app_path("Http/Controllers/BaseApi/CrudApiController.php");

        $this->info("🔍 Checking `allowedResources` in CrudApiController.php...");
        if (!$this->isResourceRegistered($controllerFile, $resourceName)) {
            $this->registerResource($controllerFile, $resourceName, $modelName);
            $this->info("✅ Added `$resourceName` to CrudApiController.");
        } else {
            $this->info("✅ `$resourceName` is already registered.");
        }

        $this->info("🔍 Checking if model `{$modelName}` exists...");
        if (!$this->checkModel($modelName)) {
            if ($this->tableExists($tableName)) {
                $this->createModel($modelName, $tableName);
                $this->info("✅ Model `{$modelName}` created from schema.");
            } else {
                $this->info("❌ Table `{$tableName}` does not exist. Creating migration...");
                $this->call('make:migration', ['name' => "create_{$tableName}_table"]);
//                $this->call('migrate');
                $this->info("⚠️ Migration created, but NOT executed! Run `php artisan migrate` manually if needed.");
                $this->createModel($modelName, $tableName);
            }
        }

        $this->info("🔍 Checking for factory support...");
        if (!$this->checkFactory($factoryFile)) {
            $this->createFactory($modelName, $tableName, $factoryFile);
            $this->info("✅ Factory `{$modelName}Factory` created.");
        }

        $this->info("🔍 Checking for CRUD tests...");
        if (!File::exists($testFile)) {
            $this->createTest($modelName, $tableName, $apiPath);
            $this->info("✅ Test cases created.");
        }
        if ($this->tableExists($tableName)) {
            $this->info("✅ Running tests...");
            $this->runTests($modelName);

        }else{
            $this->info("✅ Skipping tests...");
        }
        $this->info("🎉 API setup complete!");
    }


    private function updateModelProperties($modelName, $tableName)
    {
        $modelFile = app_path("Models/{$modelName}.php");

        if (!file_exists($modelFile)) {
            $this->warn("⚠️ Model file `{$modelFile}` does not exist. Skipping update.");
            return;
        }

        $columns = $this->getTableColumns($tableName);
        if (empty($columns)) {
            $this->warn("⚠️ Table `{$tableName}` has no columns. Skipping update.");
            return;
        }

        $timestamps = ['created_at', 'updated_at', 'deleted_at'];
        $fillableColumns = array_keys($columns);

        // Remove primary key, timestamps, and foreign keys from fillable columns
        $timestamps = ['created_at', 'updated_at', 'deleted_at'];
        $fillableColumns = array_keys($columns);

        // ✅ Ensure `_id` foreign keys are included
        foreach ($columns as $column => $details) {
            if (str_ends_with($column, '_id')) {
                $this->info("🔍 Ensuring foreign key `{$column}` is fillable...");
                $fillableColumns[] = $column;
            }
        }

        // Read model content line by line
        $modelContent = file_get_contents($modelFile);
        $lines = explode("\n", $modelContent); // ✅ Split into an array of lines
        $originalLines = $lines; // ✅ Keep a copy for change detection

        $changesMade = false;
        $foundClassDeclaration = false;
        $foundOpeningBrace = false;

        $hasTimestamps = false;
        $hasDates = false;
        $hasFillable = false;

        foreach ($lines as $index => $line) {
            $trimLine = trim($line);
            $trimmLine = preg_replace('/\s+/', ' ', $trimLine);
// ✅ Normalize spaces and remove hidden characters
            $trimmeLine = preg_replace('/\s+/', ' ', $trimmLine); // Replace multiple spaces with single space
            $trimmedLine = str_replace(["\t", "\r", "\x0B", "\0"], '', $trimmeLine); // Remove hidden characters


            // ✅ Check if properties already exist
            if (strpos($trimmedLine, 'public $timestamps = false') !== false) {
                $hasTimestamps = true;
            }
            if (strpos($trimmedLine, 'protected $dates = [') !== false) {
                $hasDates = true;
            }
            if (strpos($trimmedLine, 'protected $fillable = [') !== false) {
                $hasFillable = true;
            }
            if (strpos($trimmedLine, 'use HasFactory;') !== false) {
                $hasFactory = true;
            }
        }
        foreach ($lines as $index => $line) {
            $trimLine = trim($line);
            $trimmLine = preg_replace('/\s+/', ' ', $trimLine);
// ✅ Normalize spaces and remove hidden characters
            $trimmeLine = preg_replace('/\s+/', ' ', $trimmLine); // Replace multiple spaces with single space
            $trimmedLine = str_replace(["\t", "\r", "\x0B", "\0"], '', $trimmeLine); // Remove hidden characters
// ✅ Detect class declaration but do NOT modify it
            if (preg_match("/^class\s+{$modelName}\s+extends\s+Model\s*.*$/", $trimmedLine)) {
                $foundClassDeclaration = true;
                continue;
            }

            // ✅ Ignore `{` if it's inside a comment
            if ($foundClassDeclaration && (str_starts_with($trimmedLine, '//') || str_starts_with($trimmedLine, '/*'))) {
                continue;
            }

            // ✅ Find the actual `{` that starts the class body
            if ($foundClassDeclaration && $trimmedLine === '{') {
                $foundOpeningBrace = true;
                continue;
            }

            // ✅ Start modifying only after the actual `{`
            if ($foundOpeningBrace) {
                // ✅ Ensure `HasFactory`
                if (!$hasFactory) {
                    $this->info("🔍 Adding `HasFactory` to `{$modelName}`...");
                    array_splice($lines, $index + 1, 0, "    use HasFactory;");
                    $changesMade = true;
                }

                // ✅ Ensure `$timestamps = false;` (Only add if missing)
                if (!$hasTimestamps) {
                    $this->info("🔍 Adding `\$timestamps = false;` to `{$modelName}`...");
                    array_splice($lines, $index + 1, 0, "    // ✅ Disable Laravel's default timestamps\n    public \$timestamps = false;");
                    $changesMade = true;
                }

                // ✅ Ensure `protected $dates = ['modified', 'created'];` (Only add if missing)
                if (isset($columns['modified']) && isset($columns['created']) && !$hasDates) {
                    $this->info("🔍 Adding `protected \$dates = ['modified', 'created'];` to `{$modelName}`...");
                    array_splice($lines, $index + 1, 0, "    // ✅ Treat 'modified' and 'created' as timestamps\n    protected \$dates = ['modified', 'created'];");
                    $changesMade = true;
                }

                // ✅ Ensure `$fillable` (Only add if missing)
                if (!$hasFillable) {
                    $fillableLine = "protected \$fillable = ['" . implode("', '", $fillableColumns) . "'];";
                    $this->info("🔍 Adding `fillable` to `{$modelName}`...");
                    array_splice($lines, $index + 1, 0, "    // ✅ Allow mass assignment\n    {$fillableLine}");
                    $changesMade = true;
                }

                break; // ✅ Stop processing once properties are added
            }
        }


        // ✅ Verify content before writing
        if ($changesMade) {
            if ($originalLines === $lines) {
                $this->error("❌ No actual changes detected in `{$modelName}`. Something went wrong!");
            } else {
                $newContent = implode("\n", $lines);
                $writeResult = file_put_contents($modelFile, $newContent);

                if ($writeResult === false) {
                    $this->error("❌ Failed to write to `{$modelFile}`. Check file permissions.");
                } else {
                    $this->info("✅ Model `{$modelName}` successfully updated.");
                    $this->info("📄 File size after update: " . strlen($newContent) . " bytes.");
                }
            }
        } else {
            $this->info("✅ No updates needed for `{$modelName}`.");
        }

        // Generate Swagger PHPDoc
        $swaggerDoc = $this->generateSwaggerDoc($modelName, $tableName, $columns);

        $this->ensureModelHasMethod($modelFile);

        // Insert PHPDoc before the first function (after properties)
        $this->insertSwaggerDocInModel($modelFile, $swaggerDoc);


    }

    private function ensureModelHasMethod($modelFile)
    {
        $content = file_get_contents($modelFile);

        // Check if the file contains any public function
        if (!preg_match('/public function\s+\w+\(/', $content)) {
            $this->info("🔍 No public methods found in `{$modelFile}`. Adding `_swaggerHelper()`.");

            // Append the method at the end of the class
            $content = preg_replace('/}\s*$/', "\n\n    public function _swaggerHelper() {\n        return;\n    }\n}", $content);

            file_put_contents($modelFile, $content);
        }
    }


    /**
     * Convert snake_case to kebab-case.
     */
    private function toKebabCase($string)
    {
        return str_replace('_', '-', strtolower($string));
    }

    /**
     * Generate OpenAPI (Swagger) documentation for a model.
     *
     * The generated documentation is split into separate PHPDoc blocks for each @OA annotation.
     */
    /**
     * Generate OpenAPI (Swagger) documentation for a model.
     *
     * The generated documentation is split into separate PHPDoc blocks,
     * each attached to a dummy function. This allows developers to quickly
     * disable or isolate any chunk by commenting out the function.
     */
    private function generateSwaggerDoc($modelName, $tableName, $columns)
    {
        $resourcePath = $this->toKebabCase($tableName);
        // Generate JSON schema for model properties.
        $jsonSchema = $this->generateJsonSchema($columns);
        // Indent the JSON schema for embedding inside nested annotations.
        $jsonSchemaForItems   = $this->indentText($jsonSchema, 26);
        $jsonSchemaForContent = $this->indentText($jsonSchema, 21);

        // Dummy function for the common path.
        $swaggerPathDoc = <<<EOT
/**
 * @OA\PathItem(
 *     path="/api/v5/{$resourcePath}"
 * )
 */
public function _swaggerPath() {}
EOT;

        // Dummy function for GET list.
        $swaggerListDoc = <<<EOT
/**
 * @OA\Get(
 *     path="/api/v5/{$resourcePath}",
 *     summary="Get list of {$modelName}",
 *     tags={"{$modelName}"},
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

        // Dummy function for GET by ID.
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

        // Dummy function for POST (create)
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

// Dummy function for PUT (update)
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


        // Dummy function for DELETE.
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

        // Concatenate all dummy functions with double newlines between each.
        return $swaggerPathDoc . "\n\n" .
            $swaggerListDoc . "\n\n" .
            $swaggerViewDoc . "\n\n" .
            $swaggerCreateDoc . "\n\n" .
            $swaggerUpdateDoc . "\n\n" .
            $swaggerDeleteDoc;
    }


    /**
     * Insert Swagger PHPDoc before the first function in the model file.
     */
    private function insertSwaggerDocInModel($modelFile, $swaggerDoc)
    {
        $content = file_get_contents($modelFile);

        // Avoid duplicate Swagger PHPDocs
        if (str_contains($content, "@OA\PathItem")) {
            $this->info("✅ Swagger PHPDoc already exists in `{$modelFile}`. Skipping...");
            return;
        }

        // Find the first function in the file
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
            // If no function is found, append an empty function
            $newContent = $content . "\n" . $swaggerDoc . "\nprivate function emptyFunction() { return; }\n";
        }

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

        // Remove the trailing comma from the last property
        if (!empty($schema)) {
            $last = array_pop($schema);
            $last = rtrim($last, ",");
            $schema[] = $last;
        }

        return implode("\n", $schema);
    }

    /**
     * Map database column type to OpenAPI property type.
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
     * Indent text with a given number of spaces.
     */
    private function indentText($text, $spaces)
    {
        $indent = str_repeat(' ', $spaces);
        return implode("\n", array_map(fn($line) => $indent . $line, explode("\n", $text)));
    }



    private function isResourceRegistered($controllerFile, $resourceName)
    {
        $content = File::get($controllerFile);
        return Str::contains($content, "'$resourceName' =>");
    }

    private function registerResource($controllerFile, $resourceName, $modelName)
    {
        $content = File::get($controllerFile);
        $search = "protected \$allowedResources = [";
        $replace = "protected \$allowedResources = [\n        '{$resourceName}' => \\App\\Models\\{$modelName}::class,";

        if (!$this->isResourceRegistered($controllerFile, $resourceName)) {
            $updatedContent = Str::replaceFirst($search, $replace, $content);
            File::put($controllerFile, $updatedContent);
        }
    }
    private function checkModel($modelName)
    {
        return File::exists(app_path("Models/{$modelName}.php"));
    }

    private function tableExists($tableName)
    {
        try {
            // Convert table name to lowercase for consistency
            $tableName = strtolower($tableName);

            // Confirm database connection
            $dbName = DB::connection()->getDatabaseName();
            $this->info("🔍 Checking if table '{$tableName}' exists in database '{$dbName}'...");

            if (DB::getSchemaBuilder()->hasTable($tableName)) {
                $this->info("✅ Table '{$tableName}' exists.");
                return true;
            } else {
                $this->error("❌ Table '{$tableName}' NOT found in database '{$dbName}'.");
                return false;
            }
        } catch (\Exception $e) {
            $this->error("❌ Database error: " . $e->getMessage());
            return false;
        }
    }




    private function createModel($modelName, $tableName)
    {
        $columns = $this->getTableColumns($tableName);
        if (empty($columns)) {
            $this->warn("⚠️ Table `{$tableName}` has no columns. Skipping model creation.");
            return;
        }

        $timestamps = ['created_at', 'updated_at', 'deleted_at'];
        $fillableColumns = array_keys($columns);

        // ✅ Ensure `_id` foreign keys are included
        foreach ($columns as $column => $details) {
            if (str_ends_with($column, '_id')) {
                $this->info("🔍 Ensuring foreign key `{$column}` is fillable...");
                $fillableColumns[] = $column;
            }
        }

        // Remove primary key, timestamps, and duplicates from fillable columns
        $fillableColumns = array_unique(array_filter($fillableColumns, function ($column) use ($timestamps) {
            return !in_array($column, $timestamps);
        }));

        // Detect composite primary keys
        $primaryKeys = array_filter($columns, fn($details) => $details['is_primary']);
        $primaryKeyLine = count($primaryKeys) > 1
            ? "protected \$primaryKey = ['" . implode("', '", array_keys($primaryKeys)) . "'];"
            : '';
        $incrementingLine = '';
        if (count($primaryKeys) > 1 || (isset($columns['id']) && $columns['id']['type'] === 'string')) {
            $incrementingLine = 'public $incrementing = false;';
        }

        // ✅ Generate `$fillable` array
        $fillableLine = "protected \$fillable = ['" . implode("', '", $fillableColumns) . "'];";

        // ✅ Generate Model Stub
        $modelStub = <<<EOT
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
    use HasFactory;

    protected \$table = '{$tableName}';

    // ✅ Allow mass assignment
    {$fillableLine}

    // ✅ Disable Laravel's default timestamps
    public \$timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected \$dates = ['modified', 'created'];

    // ✅ Define the primary key
    {$primaryKeyLine}
    {$incrementingLine}

     // Override getKeyForSaveQuery to handle composite keys
    protected function getKeyForSaveQuery()
    {
        \$query = \$this->newQueryWithoutScopes();
        \$keyName = \$this->getKeyName();
        if(!is_array(\$keyName)){
            \$keyName = [\$keyName];;
        }
        foreach (\$keyName as \$key) {
            \$query->where(\$key, '=', \$this->getAttribute(\$key));
        }

        return \$query;
    }

    // Override setKeysForSaveQuery to handle composite keys
    protected function setKeysForSaveQuery(\$query)
    {
        \$keyName = \$this->getKeyName();
        if(!is_array(\$keyName)){
            \$keyName = [\$keyName];;
        }
        foreach (\$keyName as \$key) {
            \$query->where(\$key, '=', \$this->getAttribute(\$key));
        }

        return \$query;
    }

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }


}
EOT;

        File::put(app_path("Models/{$modelName}.php"), $modelStub);
        $this->info("✅ Model `{$modelName}` successfully created.");
    }

    private function checkFactory($factoryFile)
    {
        return File::exists($factoryFile);
    }

    private function createFactory($modelName, $tableName, $factoryFile)
    {
        $columns = $this->getTableColumns($tableName);
        if (empty($columns)) {
            $this->warn("⚠️ No columns found for table '{$tableName}'. Skipping factory generation.");
            return;
        }

        $fakerData = $this->generateFakerData($columns);

        // Detect composite primary keys
        $primaryKeys = array_filter($columns, fn($details) => $details['is_primary']);
        $primaryKeyConditions = '';
        if (count($primaryKeys) > 1) {
            $primaryKeyConditions = implode("\n            ", array_map(fn($key) => "\${$key} = \$this->faker->randomElement(\App\Models\\{$modelName}::pluck('{$key}')->toArray()) ?? 1;", array_keys($primaryKeys)));
        }

        $primaryKeyCheck = '';
        if (count($primaryKeys) > 1) {
            $whereConditions = '';
            foreach (array_keys($primaryKeys) as $key) {
                $whereConditions .= "->where('{$key}', \${$key})\n                ";
            }
            $whereConditions = ltrim($whereConditions, '->where('); // Remove the trailing spaces and newline
            $whereConditions = rtrim($whereConditions, ")\n                "); // Remove the trailing spaces and newline

            $primaryKeyCheck = <<<EOT
\$attempts = 0;
do {
    {$primaryKeyConditions}
    \$exists = {$modelName}::where({$whereConditions})
        ->exists();
    \$attempts++;
} while (\$exists && \$attempts < 5);
EOT;
        }

        $factoryStub = <<<EOT
<?php

namespace Database\Factories;

use App\Models\\{$modelName};
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class {$modelName}Factory extends Factory
{
    protected \$model = {$modelName}::class;

    public function definition(): array
    {
        {$primaryKeyCheck}

        return {$fakerData};
    }
}
EOT;

        File::put($factoryFile, $factoryStub);
        $this->info("✅ Factory `{$modelName}Factory` created at `{$factoryFile}`.");
    }

    private function createTest($modelName, $tableName, $apiPath)
    {
        $testFile = base_path("tests/Feature/{$modelName}ApiTest.php");
        $apiPath = trim($apiPath, '/');
        $apiPath = '/' . $apiPath;
        $columns = $this->getTableColumns($tableName);
        if (empty($columns)) {
            $this->warn("⚠️ No columns found for table '{$tableName}'. Review test generation.");
            return;
        }

        $testStub = <<<EOT
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\\{$modelName};
use App\Models\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class {$modelName}ApiTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected \$token;

    protected function setUp(): void
    {
        parent::setUp();

        \$user = TestSecurityUser::where('id', 2)->first();
        if (!\$user) {
            \$this->markTestSkipped('User with id 2 not found.');
            return;
        }
        \$this->token = JWTAuth::fromUser(\$user);
    }

    public function test_can_list_{$modelName}()
    {
        if ({$modelName}::count() === 0) {
            {$modelName}::factory()->count(3)->create();
        }

        \$response = \$this->withHeaders([
            'Authorization' => "Bearer {\$this->token}",
        ])->getJson('{$apiPath}');

        \$response->assertStatus(200);
    }

    public function test_can_create_{$modelName}()
    {
        \$record = {$modelName}::factory()->make();
        \$data = \$record->toArray();

        \$response = \$this->withHeaders([
            'Authorization' => "Bearer {\$this->token}",
        ])->postJson('{$apiPath}', \$data);

        \$response->assertStatus(201);
    }

    public function test_can_view_{$modelName}()
    {
        \$record = {$modelName}::factory()->create();
        \$response = \$this->withHeaders([
            'Authorization' => "Bearer {\$this->token}",
        ])->getJson('{$apiPath}/' . \$record->id);

        \$response->assertStatus(200);
    }


    public function test_can_update_{$modelName}()
    {
        \$record = {$modelName}::factory()->create();
        \$updatedData = [
            'id' => \$record->id,
            // Add at least one field from schema to update
        ];
        \$response = \$this->withHeaders([
            'Authorization' => "Bearer {\$this->token}",
        ])->putJson('{$apiPath}/' . \$record->id, \$updatedData);

        \$response->assertStatus(200);
    }

    public function test_can_delete_{$modelName}()
    {
        \$record = {$modelName}::factory()->create();
        \$response = \$this->withHeaders([
            'Authorization' => "Bearer {\$this->token}",
        ])->deleteJson('{$apiPath}/' . \$record->id);

        \$response->assertStatus(204);
    }
}
EOT;

        File::ensureDirectoryExists(base_path("tests/Feature/"));
        File::put($testFile, $testStub);
        $this->info("✅ Test cases generated: `{$testFile}`.");
    }

    private function runTests($modelName)
    {
        exec("php artisan test --filter={$modelName}ApiTest", $output);
        foreach ($output as $line) {
            $this->info($line);
        }
    }

    function generateFakerData($columns)
{
    $fakerFields = [];

    foreach ($columns as $column => $attributes) {
        if (in_array($column, ['created_at', 'updated_at', 'deleted_at'])) {
            continue;
        }


        // Handle different column types with limitations
        $fakerFields[$column] = match (true) {
            str_contains($attributes['type'], 'int') && preg_match('/\((\d+)\)/', $attributes['type'], $matches) => '$this->faker->numberBetween(0, ' . (10 ** $matches[1] - 1) . ')',
            str_contains($attributes['type'], 'int') => '$this->faker->numberBetween(1, 1000)',
            str_contains($attributes['type'], 'decimal') || str_contains($attributes['type'], 'float') => '$this->faker->randomFloat(2, 10, 1000)',
            str_contains($attributes['type'], 'varchar') && preg_match('/\((\d+)\)/', $attributes['type'], $matches) => '$this->faker->lexify(str_repeat("?", ' . $matches[1] . '))',
            str_contains($attributes['type'], 'text') => '$this->faker->text(50)',
            str_contains($attributes['type'], 'datetime') || str_contains($attributes['type'], 'timestamp') => '\Carbon\Carbon::now()->format("Y-m-d H:i:s")',
            str_contains($attributes['type'], 'date') => '\Carbon\Carbon::now()->format("Y-m-d")',
            str_contains($attributes['type'], 'tinyint(1)') => '$this->faker->boolean',
            default => '$this->faker->word()',
        };
        // Handle primary key
        if ($column == 'id' && empty($attributes['is_primary'])) {
            if (str_contains($attributes['type'], 'int')) {
                $fakerFields[$column] = '$this->faker->unique()->numberBetween(1, 1000)';
            } else {
                $fakerFields[$column] = '(string) \Illuminate\Support\Str::uuid()';
            }
            if ($column == 'id' && $attributes['is_primary']) {
                if (str_contains($attributes['type'], 'int')) {
                    unset($fakerFields[$column]);
//                    continue; // Skip numeric primary keys (usually auto-incrementing)
                } else {
                    $fakerFields[$column] = '(string) \Illuminate\Support\Str::uuid()';
//                    continue;
                }
            }
        }

        // Handle foreign keys properly
        if (isset($attributes['foreign_key'])) {
            $tableName = $attributes['foreign_key']['table'];
            $relatedModel = Str::studly($tableName);
            if (class_exists("\\App\\Models\\{$relatedModel}")) {
                $fakerFields[$column] = "\\App\\Models\\{$relatedModel}::inRandomOrder()->value('{$attributes['foreign_key']['column']}') ?? 1";
            } else {
                $columnName = $attributes['foreign_key']['column'];
                $relatedCount = \DB::table($tableName)->count();
                if ($relatedCount > 0) {
                    $fakerFields[$column] = "\\DB::table('{$tableName}')->inRandomOrder()->value('{$columnName}') ?? 1";
                } else {
                    $this->warn("⚠️ Model for foreign key '{$attributes['foreign_key']['table']}' not found. Please review.");
                    $fakerFields[$column] = "1"; // Default to 1 if the related model is missing
                }
            }
        }

    }

    // Convert array to executable code format
    $output = "[\n";
    foreach ($fakerFields as $key => $value) {
        // Ensure Faker and Carbon values are not quoted
        if ($value === '(string) \Illuminate\Support\Str::uuid()') {
            $output .= "    '{$key}' => {$value},\n";
        } elseif (str_starts_with($value, '$this->faker')
            || str_starts_with($value, '\\Carbon\\Carbon::now()')
            || str_starts_with($value, '\\App\\Models\\')) {
            $output .= "    '{$key}' => {$value},\n";
        } else {
            $output .= "    '{$key}' => '{$value}',\n";
        }
    }
    $output .= "]";

    return $output;
}
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




}


