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
//        $apiList = [
//            'api/v5/api-securities-scopes',
//            'api/v5/assessment-item-results',
//            'api/v5/assessment-item-results-archived',
//            'api/v5/deleted-records',
//            'api/v5/email-templates',
//            'api/v5/inserted-records',
//            'api/v5/security-user-logins',
//            'api/v5/security-user-sessions',
//            'api/v5/staff-position-titles-grades',
//            'api/v5/survey-rules',
//            'api/v5/alerts-roles',
//            'api/v5/api-credentials-scopes',
//            'api/v5/appraisal-forms-criterias',
//            'api/v5/appraisal-forms-criterias-scores',
//            'api/v5/appraisal-forms-criterias-scores-links',
//            'api/v5/appraisal-periods-types',
//            'api/v5/custom-table-cells',
//            'api/v5/education-subjects-field-of-studies',
//            'api/v5/examination-centre-special-needs',
//            'api/v5/security-group-areas',
//            'api/v5/security-role-functions',
//            'api/v5/webhook-events',
//            'api/v5/workflow-rule-events',
//            'api/v5/education-grades-subjects',
//            'api/v5/outcome-templates',
//            'api/v5/textbooks',
//            'api/v5/competency-templates',
//            'api/v5/examination-centre-rooms-examinations',
//            'api/v5/examination-centres-examinations',
//            'api/v5/outcome-criterias',
//            'api/v5/outcome-periods',
//            'api/v5/report-card-subjects',
//            'api/v5/competency-items',
//            'api/v5/competency-periods',
//            'api/v5/examination-centres-examinations-subjects',
//            'api/v5/assessment-items-grading-types',
//            'api/v5/competency-criterias',
//            'api/v5/competency-items-periods',
//            'api/v5/appraisal-dropdown-answers',
//            'api/v5/appraisal-number-answers',
//            'api/v5/appraisal-score-answers',
//            'api/v5/appraisal-slider-answers',
//            'api/v5/appraisal-text-answers',
//            'api/v5/class-profile-processes',
//            'api/v5/class-profiles',
//            'api/v5/examination-centre-rooms-examinations-invigilators',
//            'api/v5/examination-centre-rooms-examinations-students',
//            'api/v5/examination-centres-examinations-institutions',
//            'api/v5/examination-centres-examinations-invigilators',
//            'api/v5/examination-centres-examinations-students',
//            'api/v5/examination-centres-examinations-subjects-students',
//            'api/v5/examination-student-subject-results',
//            'api/v5/infrastructure-projects-needs',
//            'api/v5/institution-buses-transport-features',
//            'api/v5/institution-case-records',
//            'api/v5/institution-class-attendance-records',
//            'api/v5/institution-class-students',
//            'api/v5/institution-classes-secondary-staff',
//            'api/v5/institution-competency-item-comments',
//            'api/v5/institution-competency-period-comments',
//            'api/v5/institution-competency-results',
//            'api/v5/institution-custom-table-cells',
//            'api/v5/institution-fee-types',
//            'api/v5/institution-outcome-results',
//            'api/v5/institution-outcome-subject-comments',
//            'api/v5/institution-repeater-survey-table-cells',
//            'api/v5/institution-report-card-processes',
//            'api/v5/institution-report-cards',
//            'api/v5/institution-schedule-lessons',
//            'api/v5/institution-staff-attendances',
//            'api/v5/institution-staff-survey-table-cells',
//            'api/v5/institution-student-absence-details',
//            'api/v5/institution-student-survey-table-cells',
//            'api/v5/institution-students-report-cards',
//            'api/v5/institution-students-report-cards-comments',
//            'api/v5/institution-subject-students',
//            'api/v5/institution-survey-table-cells',
//            'api/v5/institution-textbooks',
//            'api/v5/institution-trip-days',
//            'api/v5/institution-trip-passengers',
//            'api/v5/report-card-email-processes',
//            'api/v5/report-card-processes',
//            'api/v5/scholarship-applications',
//            'api/v5/scholarship-recipients',
//            'api/v5/scholarships-field-of-studies',
//            'api/v5/scholarships-scholarship-attachment-types',
//            'api/v5/security-group-institutions',
//            'api/v5/staff-custom-table-cells',
//            'api/v5/staff-licenses-classifications',
//            'api/v5/staff-qualifications-specialisations',
//            'api/v5/staff-qualifications-subjects',
//            'api/v5/staff-report-card-email-processes',
//            'api/v5/staff-report-card-processes',
//            'api/v5/staff-report-cards',
//            'api/v5/student-attendance-marked-records',
//            'api/v5/student-custom-table-cells',
//            'api/v5/student-report-card-email-processes',
//            'api/v5/student-report-card-processes',
//            'api/v5/student-report-cards',
//            'api/v5/training-sessions-trainees',
//            'api/v5/user-attachments-roles',
//            'api/v5/user-nationalities'
//        ];
        $apiList = [
//            '/api/v5/summary-assessment-item-results',
//            '/api/v5/summary-area-institution-grade-attendances',
//            '/api/v5/summary-area-provider-grade-subject-results',
//            '/api/v5/summary-grade-gender-ages',
//            '/api/v5/summary-grade-status-genders',
//            '/api/v5/summary-institution-grade-nationalities',
//            '/api/v5/summary-institution-grades',
//            '/api/v5/summary-institution-nationalities',
//            '/api/v5/summary-institution-room-types',
//            '/api/v5/summary-institution-student-absences',
//            '/api/v5/summary-institution-student-subject-results',
//            '/api/v5/summary-institutions',
//            '/api/v5/summary-isced-sectors',
//            '/api/v5/summary-programme-sector-genders',
//            '/api/v5/summary-programme-sector-qualification-genders',
//            '/api/v5/summary-programme-sector-specialization-genders',
//            '/api/v5/summary-student-assessments',
//            '/api/v5/summary-student-attendances',
            '/api/v5/data-dictionary',
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
//                $this->addComplexKey($testFile);
                $this->changeSummaryTestCase($testFile);
            } else {
                $this->warn("Test file {$testFile} does not exist.");
            }
        }

        $this->info('Trait addition and function modification completed.');
    }

    /**
     * Normalizes a given line by trimming whitespace and removing hidden characters.
     */
    private function normalizeLine($line)
    {
        $trimLine    = trim($line);
        $trimmLine   = preg_replace('/\s+/', ' ', $trimLine);
        $trimmeLine  = preg_replace('/\s+/', ' ', $trimmLine);
        $normalized  = str_replace(["\t", "\r", "\x0B", "\0"], '', $trimmeLine);
        return $normalized;
    }


    /**
     * Change summary test cases:
     * 1) Duplicate the original view function into a "by ID" version (assertStatus 405)
     *    and a "complex" view version (assertStatus 200 with composite keys).
     * 2) Force update and delete functions to assertStatus(405).
     * 3) Use simple str_contains checks and assume each function ends with a line that (after normalization)
     *    ends with "},"
     *
     * After processing, the original three test functions are removed and replaced by four functions.
     */
    private function changeSummaryTestCase($testFile)
    {
        // Read file lines.
        $lines = file($testFile, FILE_IGNORE_NEW_LINES);
        $lineCount = count($lines);

        // We'll extract our test functions into an associative array.
        // Key: function name, Value: array of lines of the function.
        $functions = [];
        $inFunction = false;
        $currentFunctionName = '';
        $currentFunctionLines = [];
        for ($i = 0; $i < $lineCount; $i++) {
            $line = $lines[$i];
            $normLine = $this->normalizeLine($line);
            // Start of a function: look for "public function" and one of our test names.
            if (!$inFunction && str_contains($normLine, 'public function')) {
                if (
                    str_contains($normLine, 'test_can_view_') ||
                    str_contains($normLine, 'test_can_update_') ||
                    str_contains($normLine, 'test_can_delete_')
                ) {
                    $inFunction = true;
                    $currentFunctionLines = [];
                    // Get function name by splitting on space and taking the part that contains our test name.
                    $parts = explode(' ', $normLine);
                    foreach ($parts as $part) {
                        if (
                            str_contains($part, 'test_can_view_') ||
                            str_contains($part, 'test_can_update_') ||
                            str_contains($part, 'test_can_delete_')
                        ) {
                            // Remove parentheses or other trailing characters.
                            $currentFunctionName = trim(str_replace(['(', ')'], '', $part));
                            break;
                        }
                    }
                }
            }
            if ($inFunction) {
                $currentFunctionLines[] = $line;
                $normLine = $this->normalizeLine($line);
                if (str_contains($normLine, '$response->assertStatus(')) {
                    // Check if next line exists and is a closing line.
                    if (($i + 1) < $lineCount) {
                        $nextLineNorm = $this->normalizeLine($lines[$i + 1]);
                        if ($nextLineNorm === "}" || $nextLineNorm === "},") {
                            // Include the closing line.
                            $currentFunctionLines[] = $lines[$i + 1];
                            $i++; // Advance the loop index to skip the closing line.
                            // Mark the function as complete.
                            $functions[$currentFunctionName] = $currentFunctionLines;
                            $inFunction = false;
                            $currentFunctionName = '';
                            $currentFunctionLines = [];
                        }
                    }
                }
            }
        }

        // Separate functions by type.
        $canViewFunction   = null; // original view function (complex view)
        $canUpdateFunction = null;
        $canDeleteFunction = null;
        foreach ($functions as $fname => $funcLines) {
            if (str_contains($fname, 'test_can_view_') && !str_contains($fname, 'ByID')) {
                $canViewFunction = $funcLines;
            } elseif (str_contains($fname, 'test_can_update_')) {
                $canUpdateFunction = $funcLines;
            } elseif (str_contains($fname, 'test_can_delete_')) {
                $canDeleteFunction = $funcLines;
            }
        }
        if ($canViewFunction === null) {
            $this->info("No test_can_view_ function found in {$testFile}");
            return;
        }
        // Duplicate the view function for the by-ID version.
        $canViewByIdFunction = $canViewFunction;

        // --- Process the complex view function ---
        foreach ($canViewFunction as $index => $line) {
            $normLine = $this->normalizeLine($line);
            if (str_contains($normLine, "getJson(")) {
                // Look for the URL in the getJson call.
                // Append composite keys.
                $complexUrl = "'academic_period_id/' . \$record->academic_period_id . '/created/' . \$record->created";
                $line = str_replace("\$record->id", $complexUrl, $line);
                $this->info("Changed complex view getJson URL in function (complex view)");
            }

            if (str_contains($normLine, "assertStatus(")) {
                // Force expected status 200.
                $line = preg_replace("/assertStatus\(\d+\)/", "assertStatus(200)", $line);
            }
            $canViewFunction[$index] = $line;
        }

        // --- Process the by-ID view function ---
        foreach ($canViewByIdFunction as $index => $line) {
            $normLine = $this->normalizeLine($line);
            if (str_contains($normLine, 'public function test_can_view_')) {
                // Rename the function header to append "ByID"
                $line = str_replace('test_can_view_', 'test_can_view_'.'ByID_', $line);
                $this->info("Renamed view function for by-ID");
            }
            if (str_contains($normLine, "getJson(")) {
                    $line = str_replace("\$record->id", "\$record->academic_period_id", $line);
                    $this->info("Changed view ID function URL in function");
            }
            if (str_contains($normLine, "assertStatus(")) {
                // Force expected status 405.
                $line = preg_replace("/assertStatus\(\d+\)/", "assertStatus(405)", $line);
            }
            $canViewByIdFunction[$index] = $line;
        }

        // --- Process the update function: force assertStatus(405) ---
        if ($canUpdateFunction !== null) {
            foreach ($canUpdateFunction as $index => $line) {
                $normLine = $this->normalizeLine($line);
                if (str_contains($normLine, "assertStatus(")) {
                    $line = preg_replace("/assertStatus\(\d+\)/", "assertStatus(405)", $line);
                    $this->info("Changed update function assertStatus in function");
                }
                if (str_contains($normLine, "putJson(")) {
                    $line = str_replace("\$record->id", "\$record->academic_period_id", $line);
                    $this->info("Changed update function URL in function");
                }

                $canUpdateFunction[$index] = $line;
            }
        }

        // --- Process the delete function: force assertStatus(405) ---
        if ($canDeleteFunction !== null) {
            foreach ($canDeleteFunction as $index => $line) {
                $normLine = $this->normalizeLine($line);
                if (str_contains($normLine, "assertStatus(")) {
                    $line = preg_replace("/assertStatus\(\d+\)/", "assertStatus(405)", $line);
                    $this->info("Changed delete function assertStatus in function");
                }
                if (str_contains($normLine, "deleteJson(")) {
                    $line = str_replace("\$record->id", "\$record->academic_period_id", $line);
                    $this->info("Changed delete function URL in function");
                }
                $canDeleteFunction[$index] = $line;
            }
        }

        // --- Rebuild the file ---
        // Assume that our test functions start at the first occurrence of any test function.
        $startIndex = null;
        for ($i = 0; $i < $lineCount; $i++) {
            $normLine = $this->normalizeLine($lines[$i]);
            if (
                str_contains($normLine, 'public function test_can_view_') ||
                str_contains($normLine, 'public function test_can_update_') ||
                str_contains($normLine, 'public function test_can_delete_')
            ) {
                $startIndex = $i;
                break;
            }
        }
        if ($startIndex === null) {
            $headerLines = $lines;
        } else {
            $headerLines = array_slice($lines, 0, $startIndex);
        }

        // Now we append our modified functions.
        // Order: viewById, view (complex), update, delete.
        $modifiedFunctions = [];
        $modifiedFunctions[] = implode("\n", $canViewByIdFunction);
        $modifiedFunctions[] = implode("\n", $canViewFunction);
        if ($canUpdateFunction !== null) {
            $modifiedFunctions[] = implode("\n", $canUpdateFunction);
        }
        if ($canDeleteFunction !== null) {
            $modifiedFunctions[] = implode("\n", $canDeleteFunction);
        }

        $newContent = implode("\n", $headerLines) . "\n\n" . implode("\n\n", $modifiedFunctions) . "\n}\n";
        file_put_contents($testFile, $newContent);
        $this->info("Modified test file: {$testFile}");
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
            if ($add_to_test_can_view && !str_contains($trimmedLine, 'keyString = ')) {
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
            if ($add_to_test_can_update && !str_contains($trimmedLine, 'keyString = ')) {
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
            if ($add_to_test_can_delete && !str_contains($trimmedLine, 'keyString = ')) {
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
