<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

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
        $this->ExaminationOptions = TableRegistry::getTableLocator()->get('ExaminationOptions');
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
        $params = json_decode($this->args[1], true);
        $academicPeriodId = $this->getAcademicPeriodId($params['academic_period_code']);

        if (!file_exists($tempFile)) {
            $this->err("Temporary file not found: $tempFile");
            Log::write('error', "Temporary file not found: $tempFile");
            return false;
        }

        Log::write('info', 'Starting sync process with params: ' . json_encode($params));
        $this->out("Starting sync process...");

        try {
            // Read the temporary file
            $responseData = json_decode(file_get_contents($tempFile), true);
            
            if (!isset($responseData['data']) || empty($responseData['data'])) {
                $this->err("No data found in the temporary file");
                Log::write('error', "No data found in the temporary file");
                return false;
            }
            
            $data = $responseData['data'];
            
            // Begin transaction
            $connection = ConnectionManager::get('default');
            $connection->begin();
            
            try {
                // Get total count for progress reporting
                $totalCandidates = count($data);
                $this->out("Processing $totalCandidates candidates...");
                Log::write('info', "Processing $totalCandidates candidates");
                
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
                            $this->warn("Unable to find matching IDs for candidate: $candidateId");
                            Log::write('warning', "Unable to find matching IDs for candidate: $candidateId");
                            $failed++;
                            continue;
                        }
                        
                        $gradesProcessed = 0;
                        $gradesFailed = 0;
                        
                        // Process each subject/option grade
                        foreach ($candidateData['final_grade'] ?? [] as $gradeData) {
                            try {
                                $optionCode = $gradeData['examination_option_code'] ?? null;
                                $optionName = $gradeData['examination_option_name'] ?? null;
                                $gradingCode = $gradeData['examination_grading_options_code'] ?? null;
                                $mark = $gradeData['mark'] ?? null;
                                
                                // Get option ID
                                $optionId = $this->getExaminationOptionId($optionCode);
                                
                                if (!$optionId) {
                                    $this->warn("Unable to find examination option with code: $optionCode");
                                    Log::write('warning', "Unable to find examination option with code: $optionCode");
                                    $gradesFailed++;
                                    continue;
                                }
                                
                                // Get grading option ID if available
                                $gradingOptionId = null;
                                if ($gradingCode) {
                                    $gradingOptionId = $this->getGradingOptionId($gradingCode, $examinationId);
                                }
                                
                                // Process the grade
                                $result = $this->processGrade(
                                    $studentId,
                                    $examinationId,
                                    $examinationCentreId,
                                    $optionId,
                                    $mark,
                                    $gradingOptionId
                                );
                                
                                if ($result) {
                                    $gradesProcessed++;
                                } else {
                                    $gradesFailed++;
                                }
                            } catch (\Exception $e) {
                                $this->err("Error processing grade: " . $e->getMessage());
                                Log::write('error', "Error processing grade: " . $e->getMessage());
                                $gradesFailed++;
                            }
                        }
                        
                        $this->out("Candidate $candidateId: $gradesProcessed grades processed, $gradesFailed failed");
                        Log::write('info', "Candidate $candidateId: $gradesProcessed grades processed, $gradesFailed failed");
                        
                        if ($gradesFailed === 0) {
                            $success++;
                        } else {
                            $failed++;
                        }
                    } catch (\Exception $e) {
                        $this->err("Error processing candidate $candidateId: " . $e->getMessage());
                        Log::write('error', "Error processing candidate $candidateId: " . $e->getMessage());
                        $failed++;
                    }
                }
                
                // Commit transaction
                $connection->commit();
                
                $this->success("Sync completed: $success successful, $failed failed out of $totalCandidates candidates");
                Log::write('info', "Sync completed: $success successful, $failed failed out of $totalCandidates candidates");
                
                // Clean up temporary file
                unlink($tempFile);
                
                return true;
            } catch (\Exception $e) {
                $connection->rollback();
                $this->err("Error during processing: " . $e->getMessage());
                Log::write('error', "Error during processing: " . $e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            $this->err("Error: " . $e->getMessage());
            Log::write('error', "Error: " . $e->getMessage());
            return false;
        }
    }

    private function getStudentId($openemisNo)
    {
        if (!$openemisNo) {
            return null;
        }
        
        $student = $this->SecurityUsers->find()
            ->where(['openemis_no' => $openemisNo])
            ->first();
        
        return $student ? $student->id : null;
    }

    private function getExaminationId($examinationCode)
    {
        if (!$examinationCode) {
            return null;
        }
        
        $examination = $this->Examinations->find()
            ->where(['code' => $examinationCode])
            ->first();
        
        return $examination ? $examination->id : null;
    }

    private function getExaminationCentreId($examinationCentreCode)
    {
        if (!$examinationCentreCode) {
            return null;
        }
        
        $examinationCentre = $this->ExaminationCentres->find()
            ->where(['code' => $examinationCentreCode])
            ->first();
        
        return $examinationCentre ? $examinationCentre->id : null;
    }

    private function getAcademicPeriodId($academicYear)
    {
        if (!$academicYear) {
            return null;
        }
        
        $academicPeriod = $this->AcademicPeriods->find()
            ->where(['academic_period_code' => $academicYear])
            ->first();
        
        return $academicPeriod ? $academicPeriod->id : null;
    }

    private function getExaminationOptionId($optionCode)
    {
        if (!$optionCode) {
            return null;
        }
        
        $option = $this->ExaminationOptions->find()
            ->where(['code' => $optionCode])
            ->first();
        
        return $option ? $option->id : null;
    }

    private function getGradingOptionId($gradingCode, $examinationId)
    {
        if (!$gradingCode || !$examinationId) {
            return null;
        }
        
        $gradingOption = $this->ExaminationGradingOptions->find()
            ->where([
                'code' => $gradingCode,
                'examination_id' => $examinationId
            ])
            ->first();
        
        return $gradingOption ? $gradingOption->id : null;
    }

    private function processGrade($studentId, $examinationId, $examinationCentreId, $optionId, $mark, $gradingOptionId = null)
    {
        if (!$studentId || !$examinationId || !$examinationCentreId || !$optionId) {
            Log::write('warning', "Missing required ID for processing grade");
            return false;
        }
        
        // Check if a record already exists
        $existingResult = $this->ExaminationStudentSubjectResults->find()
            ->where([
                'student_id' => $studentId,
                'examination_id' => $examinationId,
                'examination_centre_id' => $examinationCentreId,
                'examination_option_id' => $optionId
            ])
            ->first();
        
        $data = [
            'student_id' => $studentId,
            'examination_id' => $examinationId,
            'examination_centre_id' => $examinationCentreId,
            'examination_option_id' => $optionId,
            'marks' => $mark
        ];
        
        if ($gradingOptionId) {
            $data['examination_grading_option_id'] = $gradingOptionId;
        }
        
        if ($existingResult) {
            // Update existing record
            $existingResult = $this->ExaminationStudentSubjectResults->patchEntity($existingResult, $data);
            Log::write('info', "Updating existing grade for student $studentId, option $optionId");
        } else {
            // Create new record
            $existingResult = $this->ExaminationStudentSubjectResults->newEntity($data);
            Log::write('info', "Creating new grade for student $studentId, option $optionId");
        }
        
        $result = $this->ExaminationStudentSubjectResults->save($existingResult);
        
        if (!$result) {
            Log::write('error', "Failed to save grade: " . json_encode($existingResult->getErrors()));
            return false;
        }
        
        return true;
    }
}