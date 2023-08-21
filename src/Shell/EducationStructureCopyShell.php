<?php
namespace App\Shell;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Console\Shell;
use Cake\Utility\Text;
class EducationStructureCopyShell extends Shell
{
    public function initialize()
    {
        $this->loadModel('Education.EducationSystem');
        parent::initialize();
    }

    public function main()
    {
        $this->out('Start Education Structure Copy Shell');
        $copyFrom = $this->args[0];
        $copyTo = $this->args[1];
        $userId=$this->args[2];
        $canCopy = $this->checkIfCanCopy($copyFrom,$copyTo);
        if ($canCopy) {
            $this->copyProcess($copyFrom, $copyTo,$userId);
        }
        $this->out('End Risk Shell');
    }

    private function checkIfCanCopy($copyFrom,$copyTo)
    {
        $canCopy = false;

        $EducationSystemTable = TableRegistry::get('Education.EducationSystems');
        $count =   $EducationSystemTable->find()->where([$EducationSystemTable->aliasField('academic_period_id') => $copyTo])->count();
        if ($count == 0) {
            $canCopy = true;
        }
        return $canCopy;
    }

    private function copyProcess($copyFrom, $copyTo,$userId)
    { 
        try {
            //copy education systems
            $EducationSystemTable = TableRegistry::get('education_systems');
            $educationSystemPreviousRecords=$EducationSystemTable->find()->where([$EducationSystemTable->aliasField('academic_period_id') => $copyFrom])->toArray();
          
            foreach( $educationSystemPreviousRecords as $key=>$educationsystem){
                $existingRecord=$EducationSystemTable->find()->where([$EducationSystemTable->aliasField('academic_period_id') => $copyTo,
                                    $EducationSystemTable->aliasField('name')=>$educationsystem->name,
                                     ])->first();
                if(empty($existingRecord)){
                        
                        $newRecord=$EducationSystemTable->newEntity(array(
                                            'name'=> $educationsystem->name,
                                            'academic_period_id'=>$copyTo,
                                            'order'=>$educationsystem->order,
                                            'visible'=>$educationsystem->visible,
                                            'modified_user_id'=>'',
                                            'modified'=>'',
                                            'created_user_id'=>$educationsystem->created_user_id,
                                            'created'=>$educationsystem->created));
                        if($resEntity=$EducationSystemTable->save($newRecord)){//saving education system
                                $savedId=$resEntity->id;
                                //copy education levels
                                $education_levels = TableRegistry::get('education_levels');
                                $educationLevelsData = $education_levels
                                ->find()
                                ->where([$education_levels->aliasField('education_system_id') =>$educationsystem->id])
                                ->All()
                                ->toArray();
                        if(!empty($educationLevelsData)){
                                $level_data_arr = [];
                                $cycle_data_arr = [];
                                $prog_data_arr = [];
                                $grade_data_arr = [];
                                $sub_data_arr = [];
                                $newLevelEntites = $newCycleEntites = [];
                                foreach ($educationLevelsData as $level_key => $level_val) {
                                                //level data
                                        $level_data_arr[$level_key]['name'] = $level_val['name'];
                                        $level_data_arr[$level_key]['order'] = $level_val['order'];
                                        $level_data_arr[$level_key]['visible'] = $level_val['visible'];
                                        $level_data_arr[$level_key]['education_system_id'] = $savedId;
                                        $level_data_arr[$level_key]['education_level_isced_id'] = $level_val['education_level_isced_id'];
                                        $level_data_arr[$level_key]['modified_user_id'] = '';
                                        $level_data_arr[$level_key]['modified'] = '';
                                        $level_data_arr[$level_key]['created_user_id'] = $userId;
                                        $level_data_arr[$level_key]['created'] = date("Y-m-d H:i:s");
                                        //insert level data
                                        $newLevelEntites = $education_levels->newEntity($level_data_arr[$level_key]);
                                        $level_result = $education_levels->save($newLevelEntites);//saving education level
                                           
                                        if(!empty($level_result)){
                                            //copy cycle data
                                            $education_cycles = TableRegistry::get('education_cycles');
                                            $educationCyclesData = $education_cycles
                                                                    ->find()
                                                                    ->where([$education_cycles->aliasField('education_level_id') => $level_val['id']])
                                                                    ->all()
                                                                    ->toArray();
                                            if(!empty($educationCyclesData)){
                                                foreach ($educationCyclesData as $cycle_key => $cycle_val) {
                                                     $cycle_data_arr[$level_key][$cycle_key]['name'] = $cycle_val['name'];
                                                     $cycle_data_arr[$level_key][$cycle_key]['admission_age'] = $cycle_val['admission_age'];
                                                     $cycle_data_arr[$level_key][$cycle_key]['order'] = $cycle_val['order'];
                                                     $cycle_data_arr[$level_key][$cycle_key]['visible'] = $cycle_val['visible'];
                                                     $cycle_data_arr[$level_key][$cycle_key]['education_level_id'] = $level_result->id;
                                                     $cycle_data_arr[$level_key][$cycle_key]['modified_user_id'] = '';
                                                     $cycle_data_arr[$level_key][$cycle_key]['modified'] = '';
                                                     $cycle_data_arr[$level_key][$cycle_key]['created_user_id'] = $userId;
                                                     $cycle_data_arr[$level_key][$cycle_key]['created'] = date("Y-m-d H:i:s");
                                                     //insert cycle data
                                                     $newCycleEntites = $education_cycles->newEntity($cycle_data_arr[$level_key][$cycle_key]);
                                                     $cycle_result = $education_cycles->save($newCycleEntites);//saving education cycle

                                                        if(!empty($cycle_result)){
                                                            //programmes data
                                                            $education_programmes = TableRegistry::get('education_programmes');
                                                            $educationProgrammesData = $education_programmes
                                                                                            ->find()
                                                                                            ->where([$education_programmes->aliasField('education_cycle_id') => $cycle_val['id']])
                                                                                            ->All()
                                                                                            ->toArray();
                                                            if(!empty($educationProgrammesData)){
                                                                foreach ($educationProgrammesData as $prog_key => $prog_val) {
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['code'] = $prog_val['code'];
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['name'] = $prog_val['name'];
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['duration'] = $prog_val['duration'];
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['order'] = $prog_val['order'];
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['visible'] = $prog_val['visible'];
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['education_field_of_study_id'] = $prog_val['education_field_of_study_id'];
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['education_cycle_id'] = $cycle_result->id;
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['education_certification_id'] = $prog_val['education_certification_id'];
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['created_user_id'] = $userId;
                                                                  $prog_data_arr[$level_key][$cycle_key][$prog_key]['created'] = date("Y-m-d H:i:s");
                                                                  //insert programmes data
                                                                  $newProgEntites = $education_programmes->newEntity($prog_data_arr[$level_key][$cycle_key][$prog_key]);
                                                                  $program_result = $education_programmes->save($newProgEntites);//saving education program
                         
                                                                  if(!empty($program_result)){
                                                                        if(!empty($program_result)){
                                                                               
                                                                            //next programmes data
                                                                            $EducationProgrammesNextProgrammesTable = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
                                                                            $nextProgrammesData = $EducationProgrammesNextProgrammesTable->find()
                                                                                                        ->where([$EducationProgrammesNextProgrammesTable->aliasField('education_programme_id') => $prog_val['id']])
                                                                                                        ->toArray();
                                    
                                                                            if (!empty($nextProgrammesData)) {
                                                                                    foreach ($nextProgrammesData as $nextProgramekey => $value) {
                                                                                       $nextProgramme_data_arr[$level_key][$cycle_key][$prog_key][$nextProgramekey]['id'] = Text::uuid();
                                                                                        $nextProgramme_data_arr[$level_key][$cycle_key][$prog_key][$nextProgramekey]['education_programme_id'] = $program_result->id;
                                                                                       $nextProgramme_data_arr[$level_key][$cycle_key][$prog_key][$nextProgramekey]['next_programme_id'] = $value['next_programme_id'];
                                                                                       
                                                                                        //insert next programmes data
                                                                                    $newNextProgramEntites = $EducationProgrammesNextProgrammesTable->newEntity($nextProgramme_data_arr[$level_key][$cycle_key][$prog_key][$nextProgramekey]);
                                                                                        $nextProgramResult = $EducationProgrammesNextProgrammesTable->save($newNextProgramEntites);//saving next program
                                                                                }
                                                                            }
                        
                                                                            //grades data
                                                                            $education_grades = TableRegistry::get('education_grades');
                                                                            $educationGradesData = $education_grades
                                                                                                            ->find()
                                                                                                            ->where([$education_grades->aliasField('education_programme_id') => $prog_val['id']])
                                                                                                            ->All()
                                                                                                            ->toArray();
                                    
                                                                            if(!empty($educationGradesData)){
                                                                                foreach ($educationGradesData as $grade_key => $grade_val) {
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['code'] = $grade_val['code'];
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['name'] = $grade_val['name'];
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['admission_age'] = $grade_val['admission_age'];
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['order'] = $grade_val['order'];
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['visible'] = $grade_val['visible'];
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['education_stage_id'] = $grade_val['education_stage_id'];
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['education_programme_id'] = $program_result->id;
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['created_user_id'] = $userId;
                                                                                    $grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]['created'] = date("Y-m-d H:i:s");
                                                                                    //insert grades data
                                                                                    $newGradeEntites = $education_grades->newEntity($grade_data_arr[$level_key][$cycle_key][$prog_key][$grade_key]);
                                                                                    $grade_result = $education_grades->save($newGradeEntites);//saving education grade
                                    
                                                                                    if(!empty($grade_result)){
                                                                                            //grades subject data
                                                                                            $education_grades_subjects = TableRegistry::get('education_grades_subjects');
                                                                                            $educationGradesSubjects = $education_grades_subjects
                                                                                                                        ->find()
                                                                                                                        ->where([$education_grades_subjects->aliasField('education_grade_id') => $grade_val['id']])
                                                                                                                        ->All()
                                                                                                                        ->toArray();
                                    
                                                                                        if(!empty($educationGradesSubjects)){
                                                                                            foreach ($educationGradesSubjects as $sub_key => $sub_val) {
                                    
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['id'] = Text::uuid();
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['hours_required'] = $sub_val['hours_required'];
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['visible'] = $sub_val['visible'];
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['auto_allocation'] = $sub_val['auto_allocation'];
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['education_grade_id'] = $grade_result->id;
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['education_subject_id'] = $sub_val['education_subject_id'];
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['created_user_id'] = $userId;
                                                                                                $sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]['created'] = date("Y-m-d H:i:s");
                                                                                                $newGradeSubEntites = $education_grades_subjects->newEntity($sub_data_arr[$level_key][$cycle_key][$prog_key][$grade_key][$sub_key]);
                                                                                                $sub_grade_result = $education_grades_subjects->save($newGradeSubEntites);//saving sub grades
                                                                                                }
                                                                                        }// if educationGradesSubjects ends
                                                                                    }// //grades subject data ends
                                                                                }
                                                                            } // if educationGradesData
                                                                    
                                                                        }//program ends
                                                                    
                                                                  }
                                                                } 
                                                            }// if educationProgrammesData
                                                        }
                                                }
                                            } // if educationCyclesData
                                        }//level ends
                                }
                        } //if educationLevelsData         
                        }//if EducationSystemTabledata end
            
                }//existing record end
                      
            }//all end

        }
        catch (\Exception $e) {
            pr($e->getMessage());
        }
    }
}
      
            
    




















	
		
