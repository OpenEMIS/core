<?php

namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

/**
 * POCOR-7510: [Add a brief summary of what this code does.]
 *
 * [Provide a detailed description of the function/class/method, its purpose, and any important details.]
 *
 * @param [type] $[param] [Description of the parameter]
 * @return [type] [Description of the return value]
 */
class SyncExamResultShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();

        // Initialize table models
        $this->AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriods');
        $this->Examinations = TableRegistry::getTableLocator()->get('Examinations');
        $this->ExaminationCentres = TableRegistry::getTableLocator()->get('ExaminationCentres');
        $this->SecurityUsers = TableRegistry::getTableLocator()->get('SecurityUsers');
        $this->EducationSubjects = TableRegistry::getTableLocator()->get('EducationSubjects');
        $this->ExaminationSubjects = TableRegistry::getTableLocator()->get('ExaminationSubjects');
        $this->ExaminationStudentSubjectResults = TableRegistry::getTableLocator()->get('ExaminationStudentSubjectResults');
        $this->ExaminationGradingOptions = TableRegistry::getTableLocator()->get('ExaminationGradingOptions');
    }

    public function getOptionParser(): \Cake\Console\ConsoleOptionParser
    {
        $parser = parent::getOptionParser();
        $parser->setDescription('Syncs exam results from OpenEMIS to ExamCore');
        $parser->addArgument('tempFile', [
            'help' => 'Path to temporary file containing exam data',
            'required' => true
        ]);
        $parser->addArgument('params', [
            'help' => 'JSON string with parameters',
            'required' => true
        ]);

        return $parser;
    }

    public function main()
    {
        $tempFile = $this->args[0];
        $this->log('=================== SYNC EXAM RESULT SHELL STARTED ===================', 'info');
        $this->log('Received tempFile argument: ' . $tempFile, 'info');

        $this->out('Received parameters: ' . json_encode($this->args));
        $this->log('Received parameters (raw args): ' . json_encode($this->args), 'info');

        // Fix: Accept both JSON with/without quotes around keys and values
        $paramsRaw = $this->args[1];
        $params = [];
        
        // Try JSON decode first
        $jsonDecoded = json_decode($paramsRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded)) {
            $params = $jsonDecoded;
        } else {
            // Fallback to manual parsing for malformed JSON
            $paramsStr = trim($paramsRaw, '{} ');
            if (!empty($paramsStr)) {
                foreach (explode(',', $paramsStr) as $pair) {
                    if (strpos($pair, ':') !== false) {
                        list($key, $value) = explode(':', $pair, 2);
                        $key = trim($key, " \t\n\r\0\x0B\"'");
                        $value = trim($value, " \t\n\r\0\x0B\"'");
                        if (!empty($key)) {
                            $params[$key] = $value;
                        }
                    }
                }
            }
        }

        $this->out('Received parameters: ' . json_encode($params));
        $this->log('Decoded parameters: ' . json_encode($params), 'info');

        $academicPeriodId = $this->getAcademicPeriodId($params['academic_period_code'] ?? null);
        $this->log('Resolved Academic Period ID: ' . json_encode($academicPeriodId), 'info');

        if (!file_exists($tempFile)) {
            $this->err("Temporary file not found: $tempFile");
            $this->log("ERROR: Temporary file not found: $tempFile", 'error');
            return false;
        }
        $this->out('Academic Period ID: ' . json_encode($academicPeriodId));

        $this->out('Starting sync process with params: ' . json_encode($params));
        $this->log('Starting sync process with params: ' . json_encode($params), 'info');

        try {
            // Read the temporary file
            $responseData = json_decode(file_get_contents($tempFile), true);

            if (!isset($responseData['data']) || empty($responseData['data'])) {
                $this->err("No data found in the temporary file");
                $this->log("ERROR: No data found in temporary file", 'error');
                return false;
            }

            $data = $responseData['data'];
            $this->log('Processing ' . count($data) . ' candidate records', 'info');

            // Begin transaction
            $connection = ConnectionManager::get('default');
            $connection->begin();

            try {
                // Get total count for progress reporting
                $totalCandidates = count($data);
                $this->out("Processing $totalCandidates candidates...");

                $processed = 0;
                $success = 0;
                $failed = 0;

                // Process each candidate's results
                foreach ($data as $candidateId => $candidateData) {
                    try {
                        $processed++;

                        // Extract candidate information
                        $openemisNo = $candidateData['openemis_no'] ?? null;
                        $examinationCode = $candidateData['examination_code'] ?? null;
                        $examinationCentreCode = $candidateData['examination_centre_code'] ?? null;
                        $academicYear = $candidateData['academic_year'] ?? null;

                        // Get corresponding IDs
                        $studentId = $this->getStudentId($openemisNo);
                        $examinationId = $this->getExaminationId($examinationCode);
                        $examinationCentreId = $this->getExaminationCentreId($examinationCentreCode);

                        if (!$studentId || !$examinationId || !$examinationCentreId || !$academicPeriodId) {
                            $this->warn("Unable to find matching IDs for candidate: $openemisNo");
                            $this->warn("Student ID: $studentId, Examination ID: $examinationId, Centre ID: $examinationCentreId, Academic Period ID: $academicPeriodId");
                            $failed++;
                            continue;
                        }

                        $gradesProcessed = 0;
                        $gradesFailed = 0;

                        // Process each subject/option grade
                        foreach ($candidateData['final_grade'] ?? [] as $gradeData) {
                            try {
                                // Validate grade data structure
                                if (empty($gradeData) || !is_array($gradeData)) {
                                    $this->warn("Invalid grade data structure for candidate: $openemisNo");
                                    $gradesFailed++;
                                    continue;
                                }

                                $optionCode = $gradeData['examination_option_code'] ?? null;
                                $optionName = $gradeData['examination_option_name'] ?? null;
                                $gradingCode = $gradeData['examination_grading_options_code'] ?? null;
                                $mark = $gradeData['mark'] ?? null;

                                // Validate required fields
                                if (empty($optionCode)) {
                                    $this->warn("Missing examination option code for candidate: $openemisNo");
                                    $gradesFailed++;
                                    continue;
                                }

                                // Get subject ID
                                $subjectId = $this->getExaminationSubjectId($optionCode);

                                if (!$subjectId) {
                                    $this->warn("Unable to find examination subject with code: $optionCode for candidate: $openemisNo");
                                    $gradesFailed++;
                                    continue;
                                }

                                // Get grading option ID if available
                                $gradingOptionId = null;
                                if ($gradingCode) {
                                    $gradingOptionId = $this->getGradingOptionId($gradingCode, $examinationId, $subjectId);
                                    if (!$gradingOptionId) {
                                        $this->warn("Unable to find grading option with code: $gradingCode for subject: $optionCode");
                                        // Continue without grading option if mark is available
                                    }
                                }

                                // Validate that we have either a mark or grading option
                                if ($mark === null && $gradingOptionId === null) {
                                    $this->warn("No mark or grading option found for candidate: $openemisNo, subject: $optionCode");
                                    $gradesFailed++;
                                    continue;
                                }

                                // Process the grade
                                $result = $this->processGrade(
                                    $studentId,
                                    $examinationId,
                                    $examinationCentreId,
                                    $subjectId,
                                    $mark,
                                    $gradingOptionId
                                );

                                if ($result) {
                                    $gradesProcessed++;
                                    $this->out("Successfully processed grade for student $openemisNo, subject $optionCode, mark: $mark");
                                } else {
                                    $gradesFailed++;
                                    $this->warn("Failed to process grade for student $openemisNo, subject $optionCode");
                                }
                            } catch (\Exception $e) {
                                $this->err("Error processing grade for candidate $openemisNo, subject $optionCode: " . $e->getMessage());
                                $this->err("Grade data: " . json_encode($gradeData));
                                $gradesFailed++;
                            }
                        }

                        $this->out("Candidate $candidateId: $gradesProcessed grades processed, $gradesFailed failed");

                        if ($gradesFailed === 0) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    } catch (\Exception $e) {
                        $this->err("Error processing candidate $candidateId: " . $e->getMessage());
                        $failed++;
                    }
                }

                // Commit transaction
                $connection->commit();
                
                $this->success("Sync completed: $success successful, $failed failed out of $totalCandidates candidates");
                $this->log("Sync completed successfully: $success successful, $failed failed out of $totalCandidates candidates", 'info');

                // Clean up temporary file
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                    $this->log("Temporary file cleaned up: $tempFile", 'info');
                }

                $this->log('=================== SYNC EXAM RESULT SHELL COMPLETED ===================', 'info');
                return true;
            } catch (\Exception $e) {
                $connection->rollback();
                $this->err("Error during processing: " . $e->getMessage());
                $this->log("ERROR during processing: " . $e->getMessage(), 'error');
                $this->log("Exception trace: " . $e->getTraceAsString(), 'error');
                
                // Clean up temporary file even on error
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                    $this->log("Temporary file cleaned up after error: $tempFile", 'info');
                }
                
                $this->log('=================== SYNC EXAM RESULT SHELL FAILED ===================', 'error');
                return false;
            }
        } catch (\Exception $e) {
            $this->err("Error: " . $e->getMessage());
            $this->log("FATAL ERROR: " . $e->getMessage(), 'error');
            $this->log("Exception trace: " . $e->getTraceAsString(), 'error');
            
            // Clean up temporary file even on fatal error
            if (file_exists($tempFile)) {
                unlink($tempFile);
                $this->log("Temporary file cleaned up after fatal error: $tempFile", 'info');
            }
            
            $this->log('=================== SYNC EXAM RESULT SHELL FATAL ERROR ===================', 'error');
            return false;
        }
    }

    private function getStudentId($openemisNo)
    {
        if (!$openemisNo) {
            $this->warn("Empty OpenEMIS number provided");
            return null;
        }

        // Clean and validate the OpenEMIS number
        $openemisNo = trim($openemisNo);
        if (empty($openemisNo)) {
            $this->warn("Invalid OpenEMIS number after trimming: '$openemisNo'");
            return null;
        }

        $student = $this->SecurityUsers->find()
            ->where(['openemis_no' => $openemisNo])
            ->first();

        if (!$student) {
            $this->warn("Student not found with OpenEMIS number: $openemisNo");
            return null;
        }

        return $student->id;
    }

    private function getExaminationId($examinationCode)
    {
        if (!$examinationCode) {
            $this->warn("Empty examination code provided");
            return null;
        }

        $examinationCode = trim($examinationCode);
        if (empty($examinationCode)) {
            $this->warn("Invalid examination code after trimming: '$examinationCode'");
            return null;
        }

        $examination = $this->Examinations->find()
            ->where(['code' => $examinationCode])
            ->first();

        if (!$examination) {
            $this->warn("Examination not found with code: $examinationCode");
            return null;
        }

        return $examination->id;
    }

    private function getExaminationCentreId($examinationCentreCode)
    {
        if (!$examinationCentreCode) {
            $this->warn("Empty examination centre code provided");
            return null;
        }

        $examinationCentreCode = trim($examinationCentreCode);
        if (empty($examinationCentreCode)) {
            $this->warn("Invalid examination centre code after trimming: '$examinationCentreCode'");
            return null;
        }

        $examinationCentre = $this->ExaminationCentres->find()
            ->where(['code' => $examinationCentreCode])
            ->first();

        if (!$examinationCentre) {
            $this->warn("Examination centre not found with code: $examinationCentreCode");
            return null;
        }

        return $examinationCentre->id;
    }

    private function getAcademicPeriodId($academicYear)
    {
        if (!$academicYear) {
            return null;
        }
        $this->out('Looking up Academic Period for code: ' . $academicYear);
        $academicPeriod = $this->AcademicPeriods->find()
            ->where(['code' => $academicYear])
            ->first();
        $this->out('Found Academic Period: ' . ($academicPeriod ? $academicPeriod->id : 'None'));
        return $academicPeriod ? $academicPeriod->id : null;
    }

    private function getExaminationSubjectId($subjectCode)
    {
        if (!$subjectCode) {
            return null;
        }

        $subject = $this->ExaminationSubjects->find()
            ->where(['code' => $subjectCode])
            ->first();

        return $subject ? $subject->id : null;
    }

    private function getGradingOptionId($gradingCode, $examinationId, $subjectId = null)
    {
        if (!$gradingCode) {
            return null;
        }

        // If we have a subject ID, get the grading type from the subject
        if ($subjectId) {
            $examinationSubject = $this->ExaminationSubjects->find()
                ->where(['id' => $subjectId])
                ->first();

            if ($examinationSubject && $examinationSubject->examination_grading_type_id) {
                $gradingOption = $this->ExaminationGradingOptions->find()
                    ->where([
                        'code' => $gradingCode,
                        'examination_grading_type_id' => $examinationSubject->examination_grading_type_id
                    ])
                    ->first();

                return $gradingOption ? $gradingOption->id : null;
            }
        }

        // Fallback: try to find grading option by code only (may not be unique)
        $gradingOption = $this->ExaminationGradingOptions->find()
            ->where(['code' => $gradingCode])
            ->first();

        return $gradingOption ? $gradingOption->id : null;
    }

    private function processGrade($studentId, $examinationId, $examinationCentreId, $optionId, $mark, $gradingOptionId = null)
    {
        if (!$studentId || !$examinationId || !$examinationCentreId || !$optionId) {
            $this->warn("Missing required ID for processing grade");
            return false;
        }

        // Validate data types and values
        if (!is_numeric($studentId) || !is_numeric($examinationId) || !is_numeric($examinationCentreId) || !is_numeric($optionId)) {
            $this->warn("Invalid ID format - IDs must be numeric");
            return false;
        }

        // Validate mark - should be numeric or null
        if ($mark !== null && !is_numeric($mark)) {
            $this->warn("Invalid mark format - mark must be numeric or null: " . $mark);
            return false;
        }

        // Check if a record already exists
        $existingResult = $this->ExaminationStudentSubjectResults->find()
            ->where([
                'student_id' => (int)$studentId,
                'examination_id' => (int)$examinationId,
                'examination_centre_id' => (int)$examinationCentreId,
                'examination_subject_id' => (int)$optionId,
            ])
            ->first();

        $data = [
            'student_id' => (int)$studentId,
            'examination_id' => (int)$examinationId,
            'examination_centre_id' => (int)$examinationCentreId,
            'examination_subject_id' => (int)$optionId,
            'marks' => $mark !== null ? (float)$mark : null
        ];

        if ($gradingOptionId && is_numeric($gradingOptionId)) {
            $data['examination_grading_option_id'] = (int)$gradingOptionId;
        }

        $data['academic_period_id'] = $this->getAcademicPeriodIdFromExamination($examinationId);
        $data['education_subject_id'] = $this->getEducationSubjectIdFromExaminationSubject($optionId);
        $data['institution_id'] = $this->getInstitutionIdFromStudent($studentId);

        // Validate required foreign keys
        if (!$data['academic_period_id'] || !$data['education_subject_id'] || !$data['institution_id']) {
            $this->warn("Missing required foreign key data - Academic Period ID: {$data['academic_period_id']}, Education Subject ID: {$data['education_subject_id']}, Institution ID: {$data['institution_id']}");
            return false;
        }

        if ($existingResult) {
            // Update existing record
            $this->out("Updating existing grade for student $studentId, option $optionId");
            $existingResult = $this->ExaminationStudentSubjectResults->patchEntity($existingResult, $data);
        } else {
            // Create new record
            $this->out("Creating new grade for student $studentId, option $optionId");
            // Generate a unique UUID for new records
            $data['id'] = $this->generateUniqueId();
            $existingResult = $this->ExaminationStudentSubjectResults->newEntity($data);
        }

        // Validate entity before saving
        if ($existingResult->hasErrors()) {
            $this->err("Entity validation failed: " . json_encode($existingResult->getErrors()));
            return false;
        }

        $result = $this->ExaminationStudentSubjectResults->save($existingResult);

        if (!$result) {
            $this->err("Failed to save grade for student $studentId, option $optionId: " . json_encode($existingResult->getErrors()));
            return false;
        }

        $this->out("Successfully saved grade for student $studentId, option $optionId");
        return true;
    }

    /**
     * Get the academic period ID from an examination ID.
     *
     * @param int|null $examinationId
     * @return int|null
     */
    private function getAcademicPeriodIdFromExamination($examinationId)
    {
        if (!$examinationId) {
            return null;
        }
        $examination = $this->Examinations->find()
            ->where(['id' => $examinationId])
            ->first();
        return $examination ? $examination->academic_period_id : null;
    }

    /**
     * Get the education subject ID from an examination subject ID.
     *
     * @param int|null $examinationSubjectId
     * @return int|null
     */
    private function getEducationSubjectIdFromExaminationSubject($examinationSubjectId)
    {
        if (!$examinationSubjectId) {
            return null;
        }
        $examinationSubject = $this->ExaminationSubjects->find()
            ->where(['id' => $examinationSubjectId])
            ->first();
        return $examinationSubject ? $examinationSubject->education_subject_id : null;
    }

    /**
     * Get the institution ID from a student ID.
     *
     * @param int|null $studentId
     * @return int|null
     */
    private function getInstitutionIdFromStudent($studentId)
    {
        if (!$studentId) {
            return null;
        }

        // Use the InstitutionStudents table to get the institution_id
        $institutionStudentsTable = TableRegistry::getTableLocator()->get('InstitutionStudents');
        $institutionStudent = $institutionStudentsTable->find()
            ->where(['student_id' => $studentId])
            ->order(['id' => 'DESC'])
            ->first();

        return $institutionStudent ? $institutionStudent->institution_id : null;
    }

    /**
     * Generate a unique ID for new records.
     * 
     * @return string
     */
    private function generateUniqueId(): string
    {
        // Generate a proper UUID v4
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
