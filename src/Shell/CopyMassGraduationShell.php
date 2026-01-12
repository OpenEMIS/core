<?php

namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;
use Cake\Utility\Text;

class CopyMassGraduationShell extends Shell
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start CopyMassGraduation Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];

        if ($this->checkEnrolledStudents($copyFrom)) {
            $this->copyProcess($copyFrom, $copyTo);
        } else {
            $this->out('No valid data to copy.');
        }

        $this->out('End CopyMassGraduation Shell');
    }

    private function checkEnrolledStudents($copyFrom)
    {
        $checkEnrolledStudents = false;

        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $count =  $InstitutionStudents->find()
            ->where([
                'student_status_id' => 1, // Enrolled students
                'academic_period_id' => $copyFrom
            ])
            ->count();
            
        $this->out('TOTAL COUNT: ' . $count);    
        if ($count != 0) {
            $checkEnrolledStudents = true;
        } else {
            $this->Alert->error('CopyData.nodataexist', ['reset' => true]);
            return false;
        }

        return $checkEnrolledStudents;
    }

    public function copyProcess($copyFrom, $copyTo)
    {
        $connection = ConnectionManager::get('default');
        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        //Check which automated service to use
        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $checkAutoEnrollmentType = $ConfigItems->value('student_automated_enrollment');
        $this->out('*************************************************');  
        $this->out('Auto Enrollment Type is : ' . $checkAutoEnrollmentType);
        $this->out('*************************************************');    

        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $academicPeriodData = $AcademicPeriods->find()
            ->select([
                $AcademicPeriods->aliasField('start_date'), 
                $AcademicPeriods->aliasField('end_date'),
                $AcademicPeriods->aliasField('start_year'), 
                $AcademicPeriods->aliasField('end_year')
            ])
            ->where([$AcademicPeriods->aliasField('id') => $copyTo])
            ->first();

            if ($academicPeriodData) {
                $startDate = $academicPeriodData->start_date->format('Y-m-d');
                $endDate = $academicPeriodData->end_date->format('Y-m-d');
                $startYear = $academicPeriodData->start_year;
                $endYear = $academicPeriodData->end_year;
            }
            



        //echo "<pre>";print_r($checkAutoEnrollmentType);exit;

        // Subquery to get distinct education_grade_id for final grades
        $finalGradeIdsQuery = "
            SELECT DISTINCT eg.id AS education_grade_id
            FROM education_systems es
            JOIN education_levels el ON es.id = el.education_system_id
            JOIN education_cycles ec ON el.id = ec.education_level_id
            JOIN education_programmes ep ON ec.id = ep.education_cycle_id
            JOIN education_grades eg ON ep.id = eg.education_programme_id
            WHERE es.academic_period_id = :copyFrom
            AND eg.order = (
                SELECT MAX(eg2.order)
                FROM education_grades eg2
                WHERE eg2.education_programme_id = ep.id
            )
        ";
    
        // Execute the final grade query and fetch the result
        $finalGradeIds = $connection->execute($finalGradeIdsQuery, ['copyFrom' => $copyFrom])->fetchAll('assoc');
    
        // Extract only the education_grade_id from the result
        $finalGradeIds = array_column($finalGradeIds, 'education_grade_id');
        $this->out('Final Grades: '); 
        //echo "<pre>";print_r($finalGradeIds);
        if (empty($finalGradeIds)) {
            $this->out('No final grades found.');
            return;  // No final grades to process
        }

        // Update students' status to 'graduated' (student_status_id = 6) and mark as massgraduated
        $studentIdsQuery = $InstitutionStudents->find()
            ->select(['student_id'])
            ->where([
                'student_status_id' => 1, // Enrolled students
                'academic_period_id' => $copyFrom,
                'education_grade_id IN' => $finalGradeIds // Use IN for filtering final grades
            ])
            ->toArray();
        $this->out('FINAL GRADE STUDENT COUNT: ' . count($studentIdsQuery)); 
        $graduatedStudentIds = array_column($studentIdsQuery, 'student_id');
        
        $this->out('*********************START - STUDENT GRADUATION FROM CURRENT INSTITUTION****************************');  
        
        // If there are students to update, perform the update
        if (!empty($graduatedStudentIds)) {
            $connection->update('institution_students', [
                'student_status_id' => 6  // Set status to graduated
                //'is_massgraduated' => 1     // Mark as mass graduated
            ], [
                'student_status_id' => 1, // Only update enrolled students
                'academic_period_id' => $copyFrom,
                'student_id IN' => $graduatedStudentIds // Only update graduated students
            ]);
        }
        $this->out('*********************END - STUDENT GRADUATION FROM CURRENT INSTITUTION****************************');  
        
        $SecurityUsers = TableRegistry::getTableLocator()->get('SecurityUsers'); // SecurityUsers table for the users
        $students = $InstitutionStudents->find()
            ->select(['InstitutionStudents.student_id', 'InstitutionStudents.id', 'InstitutionStudents.institution_id',
             'SecurityUsers.address_area_id', 'InstitutionStudents.education_grade_id', 'InstitutionStudents.created'])
            ->join([
                'table' => 'security_users',  // The table being joined
                'alias' => 'SecurityUsers',   // Alias for the joined table
                'type' => 'INNER',
                'conditions' => 'SecurityUsers.id = InstitutionStudents.student_id'
            ])
            ->where([
                'InstitutionStudents.student_status_id' => 6,
                'InstitutionStudents.academic_period_id' => $copyFrom,
                //'InstitutionStudents.is_massgraduated' => 1,
                'InstitutionStudents.student_id IN' => $graduatedStudentIds
                //'SecurityUsers.address_area_id IS NOT' => null  // Filter only those with an address_area_id
            ])
            ->order(['InstitutionStudents.institution_id', 'InstitutionStudents.education_grade_id', 'InstitutionStudents.created' => 'DESC'])
            //->limit(5)
            ->toArray();

            // echo "<pre>";"*************HERE FIRST*************************";
            // echo "<pre>";print_r($students);
        $insertData = [];
        if($checkAutoEnrollmentType == '0') {
            $this->out('************************Executing Code For Student Address Area*************************');  
            foreach ($students as $student) {
                //Fetch student's current grade
                $this->out('Current Grade: ' . $student['education_grade_id']);  

                // Fetch the next education grade based on the student's current grade and academic period
                $nextGradeOptions = $this->getNextEducationGrades($student['education_grade_id'], $copyTo);
                
                $nextGradeId = key($nextGradeOptions);  // Get the first grade ID from the next grade options
                $this->out('NextGrade: ' . $nextGradeId); 

                //check if next grade is available in the same institution
                $checkNextGradeInInstitution = $this->getNextGradeInInstitution($nextGradeId, $copyTo, $student['institution_id']);
                if($checkNextGradeInInstitution) {
                    $this->out('Next Grade is available in the same institution');
                    $this->out('NextInstitutionId: ' . $student['institution_id']); 
                    $nextInstitutionId = $student['institution_id'];
                    
                } else {
                    $this->out('Next Grade is not available in the same institution');
                    $this->out('Now find in Student Address Area Institution'); 
                    $addressAreaId = $student->SecurityUsers['address_area_id'];
                    $this->out('StudentArea: ' . $addressAreaId);   

                    //$addressAreaId = 25;
                    $checkStudentAddressAreaInstitution = $this->fetchInstitutionIdByStudentArea($addressAreaId, $nextGradeId, $copyTo);
                    //$checkStudentAddressAreaInstitution = $this->getStudentAddressAreaData($nextGradeId, $copyTo);
                    $this->out('Student Address Area Institution Data: ');
                    //echo "<pre>";print_r($checkStudentAddressAreaInstitution);
                    $nextInstitutionId = $checkStudentAddressAreaInstitution;                                 
                } 
                // If a record exists, use the ID as previous_institution_student_id
                $previousInstitutionStudentId = $student['id'] ? $student['id'] : null;   
                
                if(isset($nextInstitutionId)) {
                    $insertData[] = [
                        'id' => Text::uuid(),
                        'student_id' => $student['student_id'],
                        'student_status_id' => 1,  // Set status to enrolled
                        'academic_period_id' => $copyTo,  // Next academic period
                        'education_grade_id' => $nextGradeId,  // Next education grade
                        'institution_id' => $nextInstitutionId,  // Update institution_id to feeder_institution_id
                        'start_date' => $startDate,  // Example start date; adjust as needed
                        'start_year' => $startYear,          // Example start year; adjust as needed
                        'end_date' => $endDate,    // Example end date; adjust as needed
                        'end_year' => $endYear,            // Example end year; adjust as needed
                        'previous_institution_student_id' => $previousInstitutionStudentId,  // Set to the latest record ID
                        //'is_massgraduated' => 1,       // Set as 0 (indicating this is the new record after graduation)
                        'created' => date('Y-m-d H:i:s'),
                        'created_user_id' => 2,        // Example user ID; adjust as needed
                    ];
                }
                

            }

        } else {
            $this->out('************************Executing Code For Feeder Institution*************************');  
            foreach ($students as $student) {
                $this->out('********************************************************************************');  
                $this->out('Current Grade: ' . $student['education_grade_id']);  
                // Fetch the next education grade based on the student's current grade and academic period
                $nextGradeOptions = $this->getNextEducationGrades($student['education_grade_id'], $copyTo);
                $this->out('ALL NEXT GRADES: '); 
                echo "<pre>";print_r($nextGradeOptions);
                $nextGradeId = key($nextGradeOptions);  // Get the first grade ID from the next grade options
                $this->out('Next Grade: ' . $nextGradeId); 
                //check if next grade is available in the same institution
                $checkNextGradeInInstitution = $this->getNextGradeInInstitution($nextGradeId, $copyTo, $student['institution_id']);
                if($checkNextGradeInInstitution) {
                    $this->out('Next Grade is available in the same institution');
                    $this->out('NextInstitutionId: ' . $student['institution_id']); 
                    $feederInstitutionId = $student['institution_id'];
                    
                    
                } else {
                    $this->out('Next Grade is not available in the same institution');
                    $this->out('Now find in Feeder Institution'); 
                    
                    //if(!$checkNextGradeInInstitutionDummy) {
                        
                        $this->out('NextInstitutionId: Feeder Institution'); 
                        $checkFeederInstitution = $this->getFeederInstitutionData($student['education_grade_id'], $copyTo, $student['institution_id']);
                        $this->out('Feeder Institution Data: ');
                        // Fetching the values into separate variables
                        $feederInstitutionId = $checkFeederInstitution[0]['institution_id'];
                        $areaAdministrativeId = $checkFeederInstitution[0]['area_administrative_id'];
                        $this->out('Feeder Inst Code: ' . $feederInstitutionId); 
                        $this->out('Feeder Area Code: ' . $areaAdministrativeId); 
                        if(!empty($feederInstitutionId)) {
                            $this->out('New Enrollment Institution Details For Student');
                            $this->out('NewInstitutionId: ' . $feederInstitutionId); 
                        } else {
                            $this->out('New Enrollment Feeder Institution not available');
                        }                   
                }
                $previousInstitutionStudentId = $student['id'] ? $student['id'] : null; 
        
                // Prepare the data to insert
                $insertData[] = [
                    'id' => Text::uuid(),
                    'student_id' => $student['student_id'],
                    'student_status_id' => 1,  // Set status to enrolled
                    'academic_period_id' => $copyTo,  // Next academic period
                    'education_grade_id' => $nextGradeId,  // Next education grade
                    'institution_id' => $feederInstitutionId,  // Update institution_id to feeder_institution_id
                    'start_date' => $startDate,  // Example start date; adjust as needed
                    'start_year' => $startYear,          // Example start year; adjust as needed
                    'end_date' => $endDate,    // Example end date; adjust as needed
                    'end_year' => $endYear,            // Example end year; adjust as needed
                    'previous_institution_student_id' => $previousInstitutionStudentId,  // Set to the latest record ID
                    //'is_massgraduated' => 1,       // Set as 0 (indicating this is the new record after graduation)
                    'created' => date('Y-m-d H:i:s'),
                    'created_user_id' => 2,        // Example user ID; adjust as needed
                ];
            }
        }


    //echo "<pre>";print_r($insertData);exit;
        // Insert the new student records
        if (!empty($insertData)) {
            $fields = [
                'id','student_id','student_status_id', 'academic_period_id', 'education_grade_id', 'institution_id',
                'start_date', 'start_year', 'end_date', 'end_year', 'previous_institution_student_id',
                'created', 'created_user_id'
            ];
        
            // Escape each value and prepare values for the query
            $values = [];
            foreach ($insertData as $data) {
                // Ensure that each value is properly escaped to avoid SQL injection issues
                $escapedData = array_map([$connection, 'quote'], $data); // Use connection's quote method for escaping
                $values[] = "(" . implode(",", $escapedData) . ")";
            }
        
            // Build the final query
            $query = "INSERT INTO institution_students (" . implode(",", $fields) . ") VALUES " . implode(",", $values);
            //echo "<pre>";print_r($query);exit;
            // Execute the raw query
            $connection->query($query);
        }
        
    }

    // Function to get next education grades based on current grade and academic period
    private function getNextEducationGrades($currentGradeId, $nextPeriodId)
    {
        $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
        $nextGradeOptions = $EducationGrades->getNextEducationGrades(
            $currentGradeId, $nextPeriodId, true, true, false
        );

        return $nextGradeOptions;
    }

    // Function to check if next grade is available in the same institution
    private function getNextGradeInInstitution($nextGradeId, $nextPeriodId, $institutionId)
    {
        $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
        $inInstGradeCheck = $InstitutionGrades->checkGradeInInstitution(
            $nextGradeId, $nextPeriodId, $institutionId
        );
        if($inInstGradeCheck > 0) {
            return true;
        } else {
            return false;
        }
    }

    private function getFeederInstitutionData($nextGradeId, $nextPeriodId, $institutionId) 
    {
       // echo "here";exit;
        $FeederInstitutions = TableRegistry::getTableLocator()->get('Institution.FeederOutgoingInstitutions');
        //echo "<pre>";print_r($FeederInstitutions);exit;
        $connection = ConnectionManager::get('default'); // Get the default database connection

        // Define the raw SQL query
        $sql = "
            SELECT fi.institution_id, i.id, i.area_administrative_id
            FROM feeders_institutions fi
            LEFT JOIN institutions i ON i.id = fi.institution_id
            WHERE fi.feeder_institution_id = :feederInstitutionId
            AND fi.academic_period_id = :academicPeriodId
        ";
        $results = $connection->execute($sql, [
            'feederInstitutionId' => $institutionId,
            'academicPeriodId' => $nextPeriodId
        ])->fetchAll('assoc');  // Fetch results as an associative array
        //echo "<pre>"; print_r($results); echo "</pre>"; exit;
        return $results;
        
    }



    private function getStudentAddressAreaData($nextGradeId, $nextPeriodId) 
    {
        $studentAreaAddress = TableRegistry::getTableLocator()->get('Configurations.ConfigAutomatedStudentEnrollments');
        $connection = ConnectionManager::get('default'); // Get the default database connection
        $sql = "
            SELECT ai.`id`, ai.`academic_period_id`, ai.`institution_id`, eg.`name`, aig.`area_administrative_id` 
            FROM `area_programme_institutions` ai 
            LEFT JOIN `education_grades` eg ON eg.education_programme_id = ai.`education_programme_id` 
            LEFT JOIN `area_programme_institution_areas` aig ON aig.area_programme_institution_id = ai.`id` 
            WHERE ai.`academic_period_id` = :academicPeriodId
            AND eg.id = :gradeId
        ";
        
        // Execute the SQL query
        $results = $connection->execute($sql, [
            'gradeId' => $nextGradeId,
            'academicPeriodId' => $nextPeriodId
        ])->fetchAll('assoc');  
        
        return $results;
    }

    private function fetchInstitutionIdByStudentArea($studentArea, $nextGradeId, $nextPeriodId)
    {
        // Fetch the results by calling getStudentAddressAreaData()
        $results = $this->getStudentAddressAreaData($nextGradeId, $nextPeriodId); 
        
        // Iterate through the result set to find a match with student area
        foreach ($results as $result) {
            // Check if the student's area matches the area_administrative_id
            if ($result['area_administrative_id'] == $studentArea) {
                // Return the institution_id if there's a match
                return $result['institution_id'];
            }
        }
        
        // If no match is found, return null or any appropriate value
        return null;
    }

   
}
