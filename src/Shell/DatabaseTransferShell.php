<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;

class DatabaseTransferShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        
        $this->loadModel('SystemProcesses');
    }

    public function main()
    {
        
        if (!empty($this->args[0])) {
            $exit = false;           
            
            $academicPeriodId = $this->args[0];

            $this->out('Initializing Transfer of data ('.Time::now().')');

            $systemProcessId = $this->SystemProcesses->addProcess('DatabaseTransfer', getmypid(), 'Archive.TransferLogs', $this->args[0]);
            $this->SystemProcesses->updateProcess($systemProcessId, null, $this->SystemProcesses::RUNNING, 0);
            
            while (!$exit) {
                $recordToProcess = $this->getRecords($academicPeriodId);
                $this->out($recordToProcess);
                if ($recordToProcess) {
                    try {
                        $this->out('Dispatching event to update Database Transfer');
                        $this->out('End Update for Database Transfer Status ('. Time::now() .')');
                    } catch (\Exception $e) {
                        $this->out('Error in Database Transfer');
                        $this->out($e->getMessage());
                        $SystemProcesses->updateProcess($systemProcessId, Time::now(), $SystemProcesses::ERROR);
                    }
                } else {
                    $this->out('No records to update ('.Time::now().')');
                    $exit = true;
                }
            }
            $this->out('End Update for Database Transfer Status ('. Time::now() .')');
            $this->SystemProcesses->updateProcess($systemProcessId, Time::now(), $this->SystemProcesses::COMPLETED);
        }else{
            $this->out('Error in Database Transfer');
        }
    }

    public function getRecords($academicPeriodId)
    {
        //get archive database connection
        $connection = ConnectionManager::get('prd_cor_arc');

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $AssessmentItemResults = TableRegistry::get('Assessment.AssessmentItemResults');
        $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
        $StudentAbsences = TableRegistry::get('Report.StudentAbsences');

        $InstitutionStudentAbsenceDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $StudentAttendanceMarkType = TableRegistry::get('Attendance.StudentAttendanceMarkTypesTable');
        //institution_student_absence_days -- couldn't find model regarding this table in master branch

        $allData = $AcademicPeriods->find('all')
                                    ->select([
                                        'AcademicPeriods.id','AcademicPeriods.parent_id','AcademicPeriods.name',
                                       'AssessmentItemResults.id','AssessmentItemResults.marks','AssessmentItemResults.assessment_grading_option_id','AssessmentItemResults.student_id','AssessmentItemResults.assessment_id','AssessmentItemResults.education_subject_id','AssessmentItemResults.education_grade_id','AssessmentItemResults.academic_period_id','AssessmentItemResults.assessment_period_id','AssessmentItemResults.institution_id','AssessmentItemResults.modified_user_id','AssessmentItemResults.modified','AssessmentItemResults.created_user_id','AssessmentItemResults.created',
                                        
                                        'ClassAttendanceRecords.institution_class_id','ClassAttendanceRecords.academic_period_id','ClassAttendanceRecords.year ','ClassAttendanceRecords.month','ClassAttendanceRecords.day_1','ClassAttendanceRecords.day_2','ClassAttendanceRecords.day_3','ClassAttendanceRecords.day_4','ClassAttendanceRecords.day_5','ClassAttendanceRecords.day_6','ClassAttendanceRecords.day_7','ClassAttendanceRecords.day_8','ClassAttendanceRecords.day_9','ClassAttendanceRecords.day_10','ClassAttendanceRecords.day_11','ClassAttendanceRecords.day_12','ClassAttendanceRecords.day_13','ClassAttendanceRecords.day_14','ClassAttendanceRecords.day_15','ClassAttendanceRecords.day_16','ClassAttendanceRecords.day_17','ClassAttendanceRecords.day_18','ClassAttendanceRecords.day_19','ClassAttendanceRecords.day_20','ClassAttendanceRecords.day_21','ClassAttendanceRecords.day_22','ClassAttendanceRecords.day_23','ClassAttendanceRecords.day_24','ClassAttendanceRecords.day_25','ClassAttendanceRecords.day_26','ClassAttendanceRecords.day_27','ClassAttendanceRecords.day_28','ClassAttendanceRecords.day_29','ClassAttendanceRecords.day_30',
                                        
                                        'StudentAbsences.id','StudentAbsences.student_id','StudentAbsences.institution_id','StudentAbsences.academic_period_id','StudentAbsences.institution_class_id','StudentAbsences.date','StudentAbsences.absence_type_id','StudentAbsences.institution_student_absence_day_id','StudentAbsences.modified_user_id','StudentAbsences.modified','StudentAbsences.created_user_id','StudentAbsences.created',
                                        
                                        'StudentAbsencesPeriodDetails.student_id','StudentAbsencesPeriodDetails.institution_id','StudentAbsencesPeriodDetails.academic_period_id','StudentAbsencesPeriodDetails.institution_class_id','StudentAbsencesPeriodDetails.date','StudentAbsencesPeriodDetails.period','StudentAbsencesPeriodDetails.comment','StudentAbsencesPeriodDetails.absence_type_id','StudentAbsencesPeriodDetails.student_absence_reason_id','StudentAbsencesPeriodDetails.subject_id','StudentAbsencesPeriodDetails.modified_user_id','StudentAbsencesPeriodDetails.modified',
                                        
                                        'StudentAttendanceMarkedRecords.institution_id','StudentAttendanceMarkedRecords.academic_period_id','StudentAttendanceMarkedRecords.institution_class_id','StudentAttendanceMarkedRecords.date','StudentAttendanceMarkedRecords.period','StudentAttendanceMarkedRecords.subject_id',
                                        
                                        'StudentAttendanceMarkTypesTable.education_grade_id','StudentAttendanceMarkTypesTable.academic_period_id','StudentAttendanceMarkTypesTable.student_attendance_type_id','StudentAttendanceMarkTypesTable.attendance_per_day','StudentAttendanceMarkTypesTable.modified_user_id','StudentAttendanceMarkTypesTable.modified',
                                        'StudentAttendanceMarkTypesTable.created_user_id','StudentAttendanceMarkTypesTable.created',
                                    ])
                                    ->leftJoin(
                                        ['AssessmentItemResults' => 'assessment_item_results'],
                                        [
                                            'AssessmentItemResults.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['ClassAttendanceRecords' => 'institution_class_attendance_records'],
                                        [
                                            'ClassAttendanceRecords.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAbsences' => 'institution_student_absences'],
                                        [
                                            'StudentAbsences.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAbsencesPeriodDetails' => 'institution_student_absence_details'],
                                        [
                                            'StudentAbsencesPeriodDetails.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAttendanceMarkedRecords' => 'student_attendance_marked_records'],
                                        [
                                            'StudentAttendanceMarkedRecords.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->leftJoin(
                                        ['StudentAttendanceMarkTypesTable' => 'student_attendance_mark_types'],
                                        [
                                            'StudentAttendanceMarkTypesTable.academic_period_id = '. $academicPeriodId
                                        ])
                                    ->where([
                                        'AcademicPeriods.id' => $academicPeriodId
                                    ])
                                    ->toArray();
                                   
         //get archive database connection
         $connection = ConnectionManager::get('prd_cor_arc');
        
        if(!empty($allData) && isset($allData)){
            foreach($allData as $data){

                if(!empty($data['AssessmentItemResults'] && isset($data['AssessmentItemResults']))){

                    $connection->execute('INSERT INTO assessment_item_results VALUES ("'.$data['AssessmentItemResults']["id"].'","'.$data['AssessmentItemResults']["marks"].'","'.$data['AssessmentItemResults']["assessment_grading_option_id"].'","'.$data['AssessmentItemResults']["student_id"].'","'.$data['AssessmentItemResults']["assessment_id"].'","'.$data['AssessmentItemResults']["education_subject_id"].'","'.$data['AssessmentItemResults']["education_grade_id"].'","'.$data['AssessmentItemResults']["academic_period_id"].'","'.$data['AssessmentItemResults']["assessment_period_id"].'","'.$data['AssessmentItemResults']["institution_id"].'","'.$data['AssessmentItemResults']["modified_user_id"].'","'.$data['AssessmentItemResults']["modified"].'","'.$data['AssessmentItemResults']["created_user_id"].'","'.$data['AssessmentItemResults']["created"].'")');
                }

                if(!empty($data['ClassAttendanceRecords'] && isset($data['ClassAttendanceRecords']))){

                    $connection->execute('INSERT INTO institution_class_attendance_records VALUES ("'.$data['ClassAttendanceRecords']["institution_class_id"].'","'.$data['ClassAttendanceRecords']["academic_period_id"].'","'.$data['ClassAttendanceRecords']["year"].'","'.$data['ClassAttendanceRecords']["month"].'","'.$data['ClassAttendanceRecords']["day_1"].'","'.$data['ClassAttendanceRecords']["day_2"].'","'.$data['ClassAttendanceRecords']["day_3"].'","'.$data['ClassAttendanceRecords']["day_4"].'","'.$data['ClassAttendanceRecords']["day_5"].'","'.$data['ClassAttendanceRecords']["day_6"].'","'.$data['ClassAttendanceRecords']["day_7"].'","'.$data['ClassAttendanceRecords']["day_8"].'","'.$data['ClassAttendanceRecords']["day_9"].'","'.$data['ClassAttendanceRecords']["day_10"].'","'.$data['ClassAttendanceRecords']["day_11"].'","'.$data['ClassAttendanceRecords']["day_12"].'","'.$data['ClassAttendanceRecords']["day_13"].'","'.$data['ClassAttendanceRecords']["day_14"].'","'.$data['ClassAttendanceRecords']["day_15"].'","'.$data['ClassAttendanceRecords']["day_16"].'","'.$data['ClassAttendanceRecords']["day_17"].'","'.$data['ClassAttendanceRecords']["day_18"].'","'.$data['ClassAttendanceRecords']["day_19"].'","'.$data['ClassAttendanceRecords']["day_20"].'","'.$data['ClassAttendanceRecords']["day_21"].'","'.$data['ClassAttendanceRecords']["day_22"].'","'.$data['ClassAttendanceRecords']["day_23"].'","'.$data['ClassAttendanceRecords']["day_24"].'","'.$data['ClassAttendanceRecords']["day_25"].'","'.$data['ClassAttendanceRecords']["day_26"].'","'.$data['ClassAttendanceRecords']["day_27"].'","'.$data['ClassAttendanceRecords']["day_28"].'","'.$data['ClassAttendanceRecords']["day_29"].'","'.$data['ClassAttendanceRecords']["day_30"].'","'.$data['ClassAttendanceRecords']["day_31"].'")');
                }

                if(!empty($data['StudentAbsences'] && isset($data['StudentAbsences']))){

                    $connection->execute('INSERT INTO institution_student_absences VALUES ("'.$data['StudentAbsences']["id"].'","'.$data['StudentAbsences']["student_id"].'","'.$data['StudentAbsences']["institution_id"].'","'.$data['StudentAbsences']["academic_period_id"].'","'.$data['StudentAbsences']["institution_class_id"].'","'.$data['StudentAbsences']["date"].'","'.$data['StudentAbsences']["absence_type_id"].'","'.$data['StudentAbsences']["institution_student_absence_day_id"].'","'.$data['StudentAbsences']["modified_user_id"].'","'.$data['StudentAbsences']["modified"].'","'.$data['StudentAbsences']["created_user_id"].'","'.$data['StudentAbsences']["created"].'")');
                }

                if(!empty($data['StudentAbsencesPeriodDetails'] && isset($data['StudentAbsencesPeriodDetails']))){

                    $connection->execute('INSERT INTO institution_student_absence_details VALUES ("'.$data['StudentAbsencesPeriodDetails']["student_id"].'","'.$data['StudentAbsencesPeriodDetails']["institution_id"].'","'.$data['StudentAbsencesPeriodDetails']["academic_period_id"].'","'.$data['StudentAbsencesPeriodDetails']["institution_class_id"].'","'.$data['StudentAbsencesPeriodDetails']["date"].'","'.$data['StudentAbsencesPeriodDetails']["period"].'","'.$data['StudentAbsencesPeriodDetails']["comment"].'","'.$data['StudentAbsencesPeriodDetails']["absence_type_id"].'","'.$data['StudentAbsencesPeriodDetails']["student_absence_reason_id"].'","'.$data['StudentAbsencesPeriodDetails']["subject_id"].'","'.$data['StudentAbsencesPeriodDetails']["modified_user_id"].'","'.$data['StudentAbsencesPeriodDetails']["modified"].'","'.$data['StudentAbsencesPeriodDetails']["created_user_id"].'","'.$data['StudentAbsencesPeriodDetails']["created"].'")');

                }

                if(!empty($data['StudentAttendanceMarkedRecords'] && isset($data['StudentAttendanceMarkedRecords']))){

                    $connection->execute('INSERT INTO student_attendance_marked_records VALUES ("'.$data['StudentAttendanceMarkedRecords']["institution_id"].'","'.$data['StudentAttendanceMarkedRecords']["academic_period_id"].'","'.$data['StudentAttendanceMarkedRecords']["institution_class_id"].'","'.$data['StudentAttendanceMarkedRecords']["date"].'","'.$data['StudentAttendanceMarkedRecords']["period"].'","'.$data['StudentAttendanceMarkedRecords']["subject_id"].'")');

                }

                if(!empty($data['StudentAttendanceMarkTypesTable'] && isset($data['StudentAttendanceMarkTypesTable']))){

                    $connection->execute('INSERT INTO student_attendance_mark_types VALUES ("'.$data['StudentAttendanceMarkTypesTable']["id"].'","'.$data['StudentAttendanceMarkTypesTable']["name"].'","'.$data['StudentAttendanceMarkTypesTable']["code"].'","'.$data['StudentAttendanceMarkTypesTable']["education_grade_id"].'","'.$data['StudentAttendanceMarkTypesTable']["academic_period_id"].'","'.$data['StudentAttendanceMarkTypesTable']["student_attendance_type_id"].'","'.$data['StudentAttendanceMarkTypesTable']["attendance_per_day"].'","'.$data['StudentAttendanceMarkTypesTable']["modified_user_id "].'","'.$data['StudentAttendanceMarkTypesTable']["modified "].'","'.$data['StudentAttendanceMarkTypesTable']["created_user_id"].'","'.$data['StudentAttendanceMarkTypesTable']["created"].'")');

                }
            }

            /** Deleting all academic period associated table's data according to the requirement */

            $AssessmentItemResults->deleteAll(['academic_period_id' => $academicPeriodId]);
            $ClassAttendanceRecords->deleteAll(['academic_period_id' => $academicPeriodId]);
            $StudentAbsences->deleteAll(['academic_period_id' => $academicPeriodId]);
            $InstitutionStudentAbsenceDetails->deleteAll(['academic_period_id' => $academicPeriodId]);
            $StudentAttendanceMarkedRecords->deleteAll(['academic_period_id' => $academicPeriodId]);
            $StudentAttendanceMarkType->deleteAll(['academic_period_id' => $academicPeriodId]);
        }
        /****************************************************************************************************************************************** */
       
        return true;
    }
}