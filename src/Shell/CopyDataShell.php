<?php
namespace App\Shell;

use Exception;
use Cake\I18n\Time;
use Cake\Console\Shell;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\Date;
use Cake\Utility\Security;
use PDOException;

class CopyDataShell extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionGrades');
        
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

    
    public function getRecords($academicPeriodId){
        $connection = ConnectionManager::get('default');
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');


        // $institution_grades_result_data = $connection->prepare("SELECT education_systems.academic_period_id,correct_grade.id AS correct_grade_id,institution_grades.* FROM `institution_grades`
        // INNER JOIN education_grades wrong_grade ON wrong_grade.id = institution_grades.education_grade_id
        // INNER JOIN education_grades correct_grade ON correct_grade.code = wrong_grade.code
        // INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
        // INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
        // INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
        // INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
        // LEFT JOIN academic_periods ON institution_grades.start_date BETWEEN academic_periods.start_date AND academic_periods.end_date
        // AND academic_periods.academic_period_level_id != -1
        // AND education_systems.academic_period_id = academic_periods.id
        // WHERE correct_grade.id != institution_grades.education_grade_id");
        // $rowData = $institution_grades_result_data->execute();

        $statement = $connection->prepare("SELECT education_systems.academic_period_id AS academic_period_id ,correct_grade.id AS correct_grade_id,institution_grades.* FROM `institution_grades`
        INNER JOIN education_grades wrong_grade ON wrong_grade.id = institution_grades.education_grade_id
        INNER JOIN education_grades correct_grade ON correct_grade.code = wrong_grade.code
        INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
        INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
        INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
        INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
        LEFT JOIN academic_periods ON institution_grades.start_date BETWEEN academic_periods.start_date AND academic_periods.end_date
        AND academic_periods.academic_period_level_id != -1
        AND education_systems.academic_period_id = academic_periods.id
        WHERE correct_grade.id != institution_grades.education_grade_id");
        $statement->execute();
        $row = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach($row AS $rowData){
            $education_grade_id = $rowData['education_grade_id'];
            $institution_id = $rowData['institution_id'];
            $InstitutionGradesdata = $InstitutionGrades
                ->find()
                ->select(['start_date'])
                ->where(['education_grade_id' => $rowData['education_grade_id'],
                        'institution_id' => $rowData['institution_id']])
                ->first();
                if(!empty($InstitutionGradesdata)){
                    $AcademicPeriodsData = $AcademicPeriods
                    ->find()
                    ->select(['start_date', 'start_year'])
                    ->where(['id' => $rowData['academic_period_id']])
                    ->first();
                    if ($AcademicPeriodsData['start_date']->date instanceof Date) {
                        $start_date = $AcademicPeriodsData['start_date']->date;
                    }
                    // if ($AcademicPeriodsData['start_year'] instanceof Time || $AcademicPeriodsData['start_year'] instanceof Date) {
                        $start_year = $AcademicPeriodsData['start_year'];
                    // }

                    $InstitutionGradesdata->updateAll(
                        ['start_date' => $start_date, 'start_year' => $start_year],    //field
                        ['education_grade_id' => $education_grade_id, 'institution_id'=> $institution_id] //condition
                    );

                    // $sql = "UPDATE institution_grades SET start_date=?, start_year=? WHERE education_grade_id=? AND institution_id=?";
                    // $stmt= $connection->prepare($sql);
                    // $stmt->execute(['2020-01-01', $start_year, $education_grade_id, $institution_id]);
                }
        }

        // $InstitutionGradesdata = $InstitutionGrades
        //         ->find()
        //         ->select(['id'])
        //         ->where(['education_grade_id' => $EducationGradesDataToInsert['id'],
        //                 'institution_id' => $InstitutionsDataToInsert['id']])
        //         ->first();
        
        // $EducationSystemsData = $EducationSystems
        //     ->find()
        //     ->select(['id'])
        //     ->where(['academic_period_id' => $academicPeriodId])
        //     ->first();

        
        // $EducationLevelsData = $EducationLevels
        //     ->find()
        //     ->select(['id'])
        //     ->where(['education_system_id' => $EducationSystemsData['id']])
        //     ->toArray();


        // foreach ($EducationLevelsData as $level_key => $level_val) {
        //     $level_data_id_arr[$level_key] = $level_val['id'];
        // }  
        
        // $EducationCyclesData = $EducationCycles
        //                         ->find()
        //                         ->where([$EducationCycles->aliasField('education_level_id IN ') =>$level_data_id_arr])
        //                         ->All()
        //                         ->toArray();


        // foreach ($EducationCyclesData as $cycle_key => $cycle_val) {
        //     $cycle_data_id_arr[$cycle_key] = $cycle_val['id'];
        // }  

        // $EducationProgrammesData = $EducationProgrammes
        //                         ->find()
        //                         ->where([$EducationProgrammes->aliasField('education_cycle_id IN ') =>$cycle_data_id_arr])
        //                         ->All()
        //                         ->toArray();

        // foreach ($EducationProgrammesData as $programme_key => $programme_val) {
        //     $programme_data_id_arr[$programme_key] = $programme_val['id'];
        // }

        // $EducationGradesData = $EducationGrades
        //                         ->find()
        //                         ->where([$EducationGrades->aliasField('education_programme_id IN ') =>$programme_data_id_arr])
        //                         ->All()
        //                         ->toArray();

        // $InstitutionsData = $Institutions
        // ->find()
        // ->All()
        // ->toArray();

        // $AcademicPeriodsData = $AcademicPeriods
        //     ->find()
        //     ->select(['start_date', 'start_year'])
        //     ->where(['id' => $academicPeriodId])
        //     ->first();
        // foreach($InstitutionsData AS $InstitutionsDataToInsert){
        //     foreach($EducationGradesData AS $EducationGradesDataToInsert){
        //         $InstitutionGradesdata = $InstitutionGrades
        //         ->find()
        //         ->select(['id'])
        //         ->where(['education_grade_id' => $EducationGradesDataToInsert['id'],
        //                 'institution_id' => $InstitutionsDataToInsert['id']])
        //         ->first();
        //         if(empty($InstitutionGradesdata)){



        //             try{
        //                 $statement = $connection->prepare('INSERT INTO institution_grades 
        //                 (
        //                 education_grade_id, 
        //                 start_date,
        //                 start_year,
        //                 end_date,
        //                 end_year,
        //                 institution_id,
        //                 modified_user_id,
        //                 modified,
        //                 created_user_id,
        //                 created)
                        
        //                 VALUES (:education_grade_id, 
        //                 :start_date, 
        //                 :start_year,
        //                 :end_date,
        //                 :end_year,
        //                 :institution_id,
        //                 :modified_user_id,
        //                 :modified,
        //                 :created_user_id,
        //                 :created)');
    
        //                 $statement->execute([
        //                 'education_grade_id' => $EducationGradesDataToInsert['id'],
        //                 'start_date' => $AcademicPeriodsData['start_date'],
        //                 'start_year' => $AcademicPeriodsData['start_year'],
        //                 'end_date' => null,
        //                 'end_year' => null,
        //                 'institution_id' => $InstitutionsDataToInsert['id'],
        //                 'modified_user_id' => 2,
        //                 'modified' => date('Y-m-d H:i:s'),
        //                 'created_user_id' => 2,
        //                 'created' => date('Y-m-d H:i:s')
        //                 ]);
                    
        //             }catch (PDOException $e) {
        //                 echo "<pre>";print_r($e);die;
        //             }
        //         }
        //     }
        // }
        return true;
        
    }

    public function decrypt($encrypted_string, $secretHash) {

        $iv = substr($secretHash, 0, 16);
        $data = base64_decode($encrypted_string);
        $decryptedMessage = openssl_decrypt($data, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $decrypted = rtrim(
            $decryptedMessage
        );
        return $decrypted;
    }

}