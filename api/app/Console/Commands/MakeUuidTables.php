<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
class MakeUuidTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:uuid-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $apiList = [
            'api/v5/infrastructure-custom-forms-fields',
            'api/v5/infrastructure-custom-forms-filters',
            'api/v5/institution-custom-forms-fields',
            'api/v5/institution-custom-forms-filters',
            'api/v5/labels',
            'api/v5/land-custom-field-values',
            'api/v5/report-progress',
            'api/v5/room-custom-field-values',
            'api/v5/security-rest-sessions',
            'api/v5/single-logout',
            'api/v5/staff-custom-forms-fields',
            'api/v5/student-custom-forms-fields',
            'api/v5/survey-responses',
            'api/v5/system-errors',
            'api/v5/workflows-filters',
            'api/v5/building-custom-field-values',
            'api/v5/custom-forms-fields',
            'api/v5/custom-forms-filters',
            'api/v5/floor-custom-field-values',
            'api/v5/survey-forms-questions',
            'api/v5/survey-status-periods',
            'api/v5/workflow-steps-params',
            'api/v5/workflow-steps-roles',
            'api/v5/custom-field-values',
            'api/v5/education-programmes-next-programmes',
            'api/v5/rubric-status-periods',
            'api/v5/rubric-status-programmes',
            'api/v5/rubric-status-roles',
            'api/v5/workflow-statuses-steps',
            'api/v5/education-grades-cumulative-gpa',
            'api/v5/student-mark-type-status-grades',
            'api/v5/assessment-items',
            'api/v5/institution-class-grades',
            'api/v5/institution-class-subjects',
            'api/v5/institution-classes-custom-field-values',
            'api/v5/institution-curricular-staff',
            'api/v5/institution-curricular-students',
            'api/v5/institution-custom-field-values',
            'api/v5/institution-repeater-survey-answers',
            'api/v5/institution-staff-survey-answers',
            'api/v5/institution-student-survey-answers',
            'api/v5/institution-students',
            'api/v5/institution-students-tmp',
            'api/v5/institution-subject-staff',
            'api/v5/institution-subjects-rooms',
            'api/v5/institution-survey-answers',
            'api/v5/scholarship-application-attachments',
            'api/v5/security-group-users',
            'api/v5/security-user-password-requests',
            'api/v5/staff-behaviour-attachments',
            'api/v5/staff-custom-field-values',
            'api/v5/student-admission-custom-field-values',
            'api/v5/student-behaviour-attachments',
            'api/v5/student-custom-field-values',
            'api/v5/student-guardians',
            'api/v5/training-courses-prerequisites',
            'api/v5/training-courses-providers',
            'api/v5/training-courses-result-types',
            'api/v5/training-courses-specialisations',
            'api/v5/training-courses-target-populations',
            'api/v5/training-session-trainee-results',
            'api/v5/training-session-trainers'
        ];

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
            $factoryFile = base_path("database/factories/{$modelName}Factory.php");

            if (file_exists($modelFile)) {
                $modelContent = file_get_contents($modelFile);

                // Add use statement for UuidId trait
                $modelContent = preg_replace(
                    '/use Illuminate\\\Database\\\Eloquent\\\Model;/',
                    "use Illuminate\\Database\\Eloquent\\Model;\nuse App\\Traits\\UuidId;",
                    $modelContent
                );

                // Add use UuidId; after use HasFactory;
                $modelContent = preg_replace(
                    '/use HasFactory;/',
                    "use HasFactory;\nuse UuidId;",
                    $modelContent
                );

                // Add properties and boot method after primary key definition
                $modelContent = preg_replace(
                    '/\/\/ ✅ Define the primary key/',
                    "// ✅ Define the primary key\n\n    public \$incrementing = false;\n\n    public \$casts = [\n        'id' => 'string',\n    ];\n\n    protected static function boot()\n    {\n        parent::boot();\n        self::bootUuidId();\n    }",
                    $modelContent
                );

                file_put_contents($modelFile, $modelContent);
            }

            if (file_exists($factoryFile)) {
                $factoryContent = file_get_contents($factoryFile);

                // Comment out 'id' => $this->faker->word(),
                $factoryContent = preg_replace(
                    "/'id' => \\\$this->faker->word\\(\\),/",
                    "// 'id' => \$this->faker->word(),",
                    $factoryContent
                );

                file_put_contents($factoryFile, $factoryContent);
            }
        }

        echo "Modifications completed.";
    }
}
