<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeUuidTables extends Command
{
    protected $signature = 'make:uuid-tables';
    protected $description = 'Update models to use UUIDs and InstitutionScope where applicable';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $inputFilePath = storage_path('app/temp/apis.txt');

        if (!File::exists($inputFilePath)) {
            $this->error("❌ Input file not found: {$inputFilePath}");
            return;
        }

        $this->info("📖 Reading API list from: {$inputFilePath}");

// Read and process the file line by line
        $apiList = File::lines($inputFilePath)
            ->map(fn($line) => trim($line))
            ->filter() // Remove empty lines
            ->toArray();

// Debug: Show the first few APIs read from the file
        $this->info("✅ Loaded " . count($apiList) . " API paths.");
        $this->info("🔹 First API: " . ($apiList[0] ?? 'No APIs found'));

        foreach ($apiList as $apiPath) {
            $apiPath = trim($apiPath, '/');
            $apiPath = str_replace('_', '-', $apiPath);
            if (!str_starts_with($apiPath, 'api/v5/')) {
                $apiPath = 'api/v5/' . $apiPath;
            }
            $segments = explode('/', $apiPath);
            $resourceName = end($segments);

            $tableName = str_replace('-', '_', $resourceName);
            $modelName = Str::studly($tableName);
            $modelFile = app_path("Models/{$modelName}.php");
//            $factoryFile = base_path("database/factories/{$modelName}Factory.php");

            if (file_exists($modelFile)) {
                $modelContent = file_get_contents($modelFile);

                // Check if the model has 'institution_id' in the $fillable array
                if (preg_match('/\$fillable\s*=\s*\[.*?\'institution_id\'.*?\]/s', $modelContent)) {
                    echo "✅ Found 'institution_id' in: {$modelFile}\n";

                    // Add use statement for InstitutionScope trait
                    if (!str_contains($modelContent, 'use App\\Traits\\InstitutionScope;')) {
                        echo "🔹 Adding 'use App\\Traits\\InstitutionScope;' to: {$modelFile}\n";
                        $modelContent = preg_replace(
                            '/use Illuminate\\\Database\\\Eloquent\\\Model;/',
                            "use Illuminate\\Database\\Eloquent\\Model;\nuse App\\Traits\\InstitutionScope;",
                            $modelContent
                        );
                    } else {
                        echo "ℹ️ 'use App\\Traits\\InstitutionScope;' already exists in: {$modelFile}\n";
                    }

                    // Add use InstitutionScope; after use HasFactory;
                    if (!str_contains($modelContent, 'use InstitutionScope;')) {
                        echo "🔹 Adding 'use InstitutionScope;' to: {$modelFile}\n";
                        $modelContent = preg_replace(
                            '/use HasFactory;/',
                            "use HasFactory;\nuse InstitutionScope;",
                            $modelContent
                        );
                    } else {
                        echo "ℹ️ 'use InstitutionScope;' already exists in: {$modelFile}\n";
                    }
                }


                // Add use statement for UuidId trait
//                if (!str_contains($modelContent, 'use App\\Traits\\UuidId;')) {
//                    $modelContent = preg_replace(
//                        '/use Illuminate\\\Database\\\Eloquent\\\Model;/',
//                        "use Illuminate\\Database\\Eloquent\\Model;\nuse App\\Traits\\UuidId;",
//                        $modelContent
//                    );
//                }

                // Add use UuidId; after use HasFactory;
//                if (!str_contains($modelContent, 'use UuidId;')) {
//                    $modelContent = preg_replace(
//                        '/use HasFactory;/',
//                        "use HasFactory;\nuse UuidId;",
//                        $modelContent
//                    );
//                }

                // Add properties and boot method after primary key definition
//                if (!str_contains($modelContent, 'public $incrementing = false;')) {
//                    $modelContent = preg_replace(
//                        '/\/\/ ✅ Define the primary key/',
//                        "// ✅ Define the primary key\n\n    public \$incrementing = false;\n\n    public \$casts = [\n        'id' => 'string',\n    ];\n\n    protected static function boot()\n    {\n        parent::boot();\n        self::bootUuidId();\n    }",
//                        $modelContent
//                    );
//                }

                file_put_contents($modelFile, $modelContent);
            }

//            if (file_exists($factoryFile)) {
//                $factoryContent = file_get_contents($factoryFile);
//
//                // Comment out 'id' => $this->faker->word(),
//                if (!str_contains($factoryContent, "// 'id' =>")) {
//                    $factoryContent = preg_replace(
//                        "/'id' => \\\$this->faker->word\\(\\),/",
//                        "// 'id' => \$this->faker->word(),",
//                        $factoryContent
//                    );
//                }
//
//                file_put_contents($factoryFile, $factoryContent);
//            }
        }

        echo "Modifications completed.";
    }
}
