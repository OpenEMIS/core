<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakePrimaryKeyString extends Command
{
    protected $signature = 'make:primary-key-string';
    protected $description = 'Add PrimaryKeyStringTrait to test feature files';

    public function handle()
    {
        $apiList = [
            'api/v5/api-securities-scopes',
            'api/v5/assessment-item-results',
            'api/v5/assessment-item-results-archived',
            'api/v5/deleted-records',
            'api/v5/email-templates',
            'api/v5/inserted-records',
            'api/v5/security-user-logins',
            'api/v5/security-user-sessions',
            'api/v5/staff-position-titles-grades',
            'api/v5/survey-rules',
            'api/v5/alerts-roles',
            'api/v5/api-credentials-scopes',
            'api/v5/appraisal-forms-criterias',
            'api/v5/appraisal-forms-criterias-scores',
            'api/v5/appraisal-forms-criterias-scores-links',
            'api/v5/appraisal-periods-types',
            'api/v5/custom-table-cells',
            'api/v5/education-subjects-field-of-studies',
            'api/v5/examination-centre-special-needs',
            'api/v5/security-group-areas',
            'api/v5/security-role-functions',
            'api/v5/webhook-events',
            'api/v5/workflow-rule-events',
            'api/v5/education-grades-subjects',
            'api/v5/outcome-templates',
            'api/v5/textbooks',
            'api/v5/competency-templates',
            'api/v5/examination-centre-rooms-examinations',
            'api/v5/examination-centres-examinations',
            'api/v5/outcome-criterias',
            'api/v5/outcome-periods',
            'api/v5/report-card-subjects',
            'api/v5/competency-items',
            'api/v5/competency-periods',
            'api/v5/examination-centres-examinations-subjects',
            'api/v5/assessment-items-grading-types',
            'api/v5/competency-criterias',
            'api/v5/competency-items-periods',
            'api/v5/appraisal-dropdown-answers',
            'api/v5/appraisal-number-answers',
            'api/v5/appraisal-score-answers',
            'api/v5/appraisal-slider-answers',
            'api/v5/appraisal-text-answers',
            'api/v5/class-profile-processes',
            'api/v5/class-profiles',
            'api/v5/examination-centre-rooms-examinations-invigilators',
            'api/v5/examination-centre-rooms-examinations-students',
            'api/v5/examination-centres-examinations-institutions',
            'api/v5/examination-centres-examinations-invigilators',
            'api/v5/examination-centres-examinations-students',
            'api/v5/examination-centres-examinations-subjects-students',
            'api/v5/examination-student-subject-results',
            'api/v5/infrastructure-projects-needs',
            'api/v5/institution-buses-transport-features',
            'api/v5/institution-case-records',
            'api/v5/institution-class-attendance-records',
            'api/v5/institution-class-students',
            'api/v5/institution-classes-secondary-staff',
            'api/v5/institution-competency-item-comments',
            'api/v5/institution-competency-period-comments',
            'api/v5/institution-competency-results',
            'api/v5/institution-custom-table-cells',
            'api/v5/institution-fee-types',
            'api/v5/institution-outcome-results',
            'api/v5/institution-outcome-subject-comments',
            'api/v5/institution-repeater-survey-table-cells',
            'api/v5/institution-report-card-processes',
            'api/v5/institution-report-cards',
            'api/v5/institution-schedule-lessons',
            'api/v5/institution-staff-attendances',
            'api/v5/institution-staff-survey-table-cells',
            'api/v5/institution-student-absence-details',
            'api/v5/institution-student-survey-table-cells',
            'api/v5/institution-students-report-cards',
            'api/v5/institution-students-report-cards-comments',
            'api/v5/institution-subject-students',
            'api/v5/institution-survey-table-cells',
            'api/v5/institution-textbooks',
            'api/v5/institution-trip-days',
            'api/v5/institution-trip-passengers',
            'api/v5/report-card-email-processes',
            'api/v5/report-card-processes',
            'api/v5/scholarship-applications',
            'api/v5/scholarship-recipients',
            'api/v5/scholarships-field-of-studies',
            'api/v5/scholarships-scholarship-attachment-types',
            'api/v5/security-group-institutions',
            'api/v5/staff-custom-table-cells',
            'api/v5/staff-licenses-classifications',
            'api/v5/staff-qualifications-specialisations',
            'api/v5/staff-qualifications-subjects',
            'api/v5/staff-report-card-email-processes',
            'api/v5/staff-report-card-processes',
            'api/v5/staff-report-cards',
            'api/v5/student-attendance-marked-records',
            'api/v5/student-custom-table-cells',
            'api/v5/student-report-card-email-processes',
            'api/v5/student-report-card-processes',
            'api/v5/student-report-cards',
            'api/v5/training-sessions-trainees',
            'api/v5/user-attachments-roles',
            'api/v5/user-nationalities'
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
            $testFile = base_path("tests/Feature/{$modelName}ApiTest.php");

            if (File::exists($testFile)) {
//                $this->addTrait($testFile);
                $this->addComplexKey($testFile);
            } else {
                $this->warn("Test file {$testFile} does not exist.");
            }
        }

        $this->info('Trait addition and function modification completed.');
    }

    private function addTrait($testFile)
    {
        $content = File::get($testFile);
        if (!Str::contains($content, 'use Tests\Traits\PrimaryKeyStringTrait;')) {
            $content = preg_replace('/<\?php\s+namespace Tests\\Feature;/', "<?php\n\nnamespace Tests\\Feature;\nuse Tests\\Traits\\PrimaryKeyStringTrait;", $content);
            $content = preg_replace('/class\s+\w+\s+extends\s+TestCase\s*{/', "$0\n    use PrimaryKeyStringTrait;", $content);
            File::put($testFile, $content);
            $this->info("Added PrimaryKeyStringTrait to {$testFile}");
        } else {
            $this->info("PrimaryKeyStringTrait already exists in {$testFile}");
        }
    }

    private function addComplexKey($testFile)
    {
        $content = file_get_contents($testFile);
        $lines = explode("\n", $content);
        $originalLines = $lines; // Keep a copy for change detection

        $changesMade = false;
        $add_to_test_can_view = false;
        $found_test_can_view = false;
        $add_to_test_can_update = false;
        $found_test_can_update = false;
        $add_to_test_can_delete = false;
        $found_test_can_delete = false;

        foreach ($lines as $index => $line) {
            $trimLine = trim($line);
            $trimmLine = preg_replace('/\s+/', ' ', $trimLine);
            $trimmeLine = preg_replace('/\s+/', ' ', $trimmLine); // Replace multiple spaces with single space
            $trimmedLine = str_replace(["\t", "\r", "\x0B", "\0"], '', $trimmeLine); // Remove hidden characters

            // Check for view function
            if (str_contains($trimmedLine, 'public function test_can_view_')) {
                $found_test_can_view = true;
                $this->info("Found test_can_view_ function");
            }
            if ($found_test_can_view) {
                if (str_contains($trimmedLine, 'record =')) {
                    $add_to_test_can_view = true;
                    $this->info("Found add_to_test_can_view");
                }
            }
            if ($add_to_test_can_view) {
                if (str_contains($trimmedLine, 'keyString = ')) {
                    $add_to_test_can_view = false;
                    $this->info("Found keyString = ");
                }
            }
            if ($add_to_test_can_view) {
                if (str_contains($trimmedLine, ". \$keyString")) {
                    $originalLines[$index - 2] = "\\\\ change is made \n        \$keyString = \$this->getPrimaryKeyString(\$record);\n" . $originalLines[$index - 2];
                    $this->info("added keyString");
                    $add_to_test_can_view = false;
                    $changesMade = true;
                }
            }

            // Check for update function
            if (str_contains($trimmedLine, 'public function test_can_update_')) {
                $found_test_can_update = true;
                $this->info("Found test_can_update_ function");
            }
            if ($found_test_can_update) {
                if (str_contains($trimmedLine, 'record =')) {
                    $add_to_test_can_update = true;
                    $this->info("Found add_to_test_can_update");
                }
            }
            if ($add_to_test_can_update) {
                if (str_contains($trimmedLine, 'keyString = ')) {
                    $add_to_test_can_update = false;
                    $this->info("Found keyString = ");
                }
            }
            if ($add_to_test_can_update) {
                if (str_contains($trimmedLine, ". \$keyString")) {
                    $originalLines[$index - 2] = "\\\\ change is made \n        \$keyString = \$this->getPrimaryKeyString(\$record);\n" . $originalLines[$index - 2];
                    $this->info("added keyString");
                    $add_to_test_can_update = false;
                    $changesMade = true;
                }
            }

            // Check for delete function
            if (str_contains($trimmedLine, 'public function test_can_delete_')) {
                $found_test_can_delete = true;
                $this->info("Found test_can_delete_ function");
            }
            if ($found_test_can_delete) {
                if (str_contains($trimmedLine, 'record =')) {
                    $add_to_test_can_delete = true;
                    $this->info("Found add_to_test_can_delete");
                }
            }
            if ($add_to_test_can_delete) {
                if (str_contains($trimmedLine, 'keyString = ')) {
                    $add_to_test_can_delete = false;
                    $this->info("Found keyString = ");
                }
            }
            if ($add_to_test_can_delete) {
                if (str_contains($trimmedLine, ". \$keyString")) {
                    $originalLines[$index - 2] = "\\\\ change is made \n        \$keyString = \$this->getPrimaryKeyString(\$record);\n" . $originalLines[$index - 2];
                    $this->info("added keyString");
                    $add_to_test_can_delete = false;
                    $changesMade = true;
                }
            }
        }

        // Write changes back to the file if any changes were made
        if ($changesMade) {
            file_put_contents($testFile, implode("\n", $originalLines));
            $this->info("Modified functions in {$testFile}");
        } else {
            $this->info("No changes made to {$testFile}");
        }
    }
}
