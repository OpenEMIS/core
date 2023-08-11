<?php
namespace Archive\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use App\Model\Traits\MessagesTrait;
use Cake\I18n\Date;

/**
 * DeletedLogs Model
 *
 * @property \Cake\ORM\Association\BelongsTo $AcademicPeriods
 *
 * @method \Archive\Model\Entity\DeletedLog get($primaryKey, $options = [])
 * @method \Archive\Model\Entity\DeletedLog newEntity($data = null, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog[] newEntities(array $data, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Archive\Model\Entity\DeletedLog patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog[] patchEntities($entities, array $data, array $options = [])
 * @method \Archive\Model\Entity\DeletedLog findOrCreate($search, callable $callback = null, $options = [])
 */
class DataManagementCopyTable extends ControllerActionTable
{
    use MessagesTrait;

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('data_management_copy');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->belongsTo('AcademicPeriods', [
            'foreignKey' => 'from_academic_period',
            'joinType' => 'INNER',
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator->integer('id')->allowEmpty('id', 'create');
        $validator->requirePresence('from_academic_period', 'create')->notEmpty('from_academic_period');
        $validator->requirePresence('to_academic_period', 'create')->notEmpty('from_academic_period');
        $validator->requirePresence('features', 'create')->notEmpty('from_academic_period');
        // $validator->allowEmpty('name', 'create');
        // $validator->allowEmpty('path', 'create');
        // $validator->dateTime('generated_on')->allowEmpty('generated_on', 'create');
        // $validator->allowEmpty('generated_by', 'create');
        return $validator;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('from_academic_period',['sort' => false]);
        $this->field('to_academic_period', ['sort' => false]);
        $this->field('features', ['sort' => false]);
        $this->field('created_user_id');
        $this->field('created');
        $this->field('created_user_id', ['visible' => true]);
        $this->field('created', ['sort' => false, 'visible' => true]);

        $this->setFieldOrder(['from_academic_period', 'to_academic_period', 'features', 'created_user_id', 'created']);
    }

    public function addBeforeAction(Event $event, ArrayObject $extram)
    {
        $condition = [];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        $this->field('from_academic_period', ['type' => 'select', 'onChangeReload'=>true, 'options' => $academicPeriodOptions]);
        $this->field('to_academic_period', ['type' => 'select', 'options' => $academicPeriodOptions]);
        $this->field('features', ['type' => 'select', 'options' => $this->getFeatureOptions()]);
        $this->setFieldOrder(['from_academic_period','to_academic_period','features']);

    }

    public function onUpdateFieldFromAcademicPeriod(Event $event, array $attr, $action, Request $request)
    {
        $condition = [];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        $attr['options'] = $academicPeriodOptions;
		$attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldToAcademicPeriod(Event $event, array $attr, $action, Request $request)
    {
        $condition = [$this->AcademicPeriods->aliasField('id').' <> ' => $request['data']['DataManagementCopy']['from_academic_period']];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        // list($periodOptions, $selectedPeriod) = array_values($this->AcademicPeriods->getYearList(['conditions' => $condition]));

        $attr['options'] = $academicPeriodOptions;
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function getAcademicPeriodOptions($querystringPeriod)
    {
        $periodOptions = $this->AcademicPeriods->getYearList();

        if ($querystringPeriod) {
            $selectedPeriod = $querystringPeriod;
        } else {
            $selectedPeriod = $this->AcademicPeriods->getCurrent();
        }

        return compact('periodOptions', 'selectedPeriod');
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){
        ini_set('memory_limit', '2G'); //POCOR-6893
        if($entity->from_academic_period == $entity->to_academic_period){
            $this->Alert->error('CopyData.genralerror', ['reset' => true]);
            return false;
        }

        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $EducationSystems = TableRegistry::get('Education.EducationSystems');

        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');

        $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
        $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
        $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
        $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
        $Institutions = TableRegistry::get('Institution.Institutions');
        
        if($entity->to_academic_period){
            
            $ToAcademicPeriodsData = $AcademicPeriods
            ->find()
            ->select(['start_date', 'start_year','end_date'])
            ->where(['id' => $entity->to_academic_period])
            ->first();

            $EducationSystemsdata = $EducationSystems
                ->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])
                ->toArray();
            //POCOR-7678
            if($entity->features =="Education Structure"){
                    if(!empty($EducationSystemsdata)){
                        $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                        return false;
                    }
            }
            else{
            if(empty($EducationSystemsdata)){
                $this->Alert->error('CopyData.nodataexisteducationsystem', ['reset' => true]);
                return false;
            }
            }
        }
        if($entity->features == 'Institution Programmes, Grades and Subjects'){
            $EducationSystemsdata = $EducationSystems
                ->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])
                ->toArray();
                
            $level_data_id_arr = [];
            if(!empty($EducationSystemsdata)){
                $EducationLevelsData = $EducationLevels
                ->find('all')
                ->where(['education_system_id' => $EducationSystemsdata[0]->id])
                ->toArray();
                foreach ($EducationLevelsData as $level_key => $level_val) {
                    $level_data_id_arr[$level_key] = $level_val['id'];
                }
            }

            $cycle_data_id_arr = [];
            if(!empty($level_data_id_arr )){
                $EducationCyclesData = $EducationCycles
                ->find('all')
                ->where(['education_level_id IN' => $level_data_id_arr])
                ->toArray();
                foreach ($EducationCyclesData as $cycle_key => $cycle_val) {
                    $cycle_data_id_arr[$cycle_key] = $cycle_val['id'];
                }
            }

            $programmes_data_id_arr = [];
            if(!empty($cycle_data_id_arr )){
                $EducationProgrammesData = $EducationProgrammes
                ->find('all')
                ->where(['education_cycle_id IN' => $cycle_data_id_arr])
                ->toArray();
                foreach ($EducationProgrammesData as $programmes_key => $programmes_val) {
                    $programmes_data_id_arr[$programmes_key] = $programmes_val['id'];
                }
            }

            $education_grades_id_arr = [];
            if(!empty($programmes_data_id_arr )){
                $EducationGradesdata = $EducationGrades
                ->find('all')
                ->where(['education_programme_id IN' => $programmes_data_id_arr])
                ->toArray();
                foreach ($EducationGradesdata as $education_grades_key => $education_grades_val) {
                    $education_grades_id_arr[$education_grades_key] = $education_grades_val['id'];
                }
            }

            if(!empty($education_grades_id_arr )){
                
                $InstitutionGradesdata = $InstitutionGrades
                ->find('all')
                ->where(['education_grade_id IN ' => $education_grades_id_arr])
                ->toArray();
                if(!empty($InstitutionGradesdata)){
                  
                    if($this->checkInstitutionCopiedData($entity->from_academic_period,$entity->to_academic_period)){//POCOR-7567-institution programme
                     
                    $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                    return false;
                    }
                }
            }
        }
        if($entity->features == 'Shifts'){
            $InstitutionShiftsData = $InstitutionShifts
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();
            if(!empty($InstitutionShiftsData)){
                if($this->checkshiftCopiedData($entity->from_academic_period,$entity->to_academic_period)){//POCOR-7576-shifts
                   $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);//POCOR-7576-shifts
                   return false;}//POCOR-7576-shifts
            }
        }
        if($entity->features == 'Infrastructure'){
            $InstitutionBuildingsData = $InstitutionBuildings
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();

            $InstitutionFloorsData = $InstitutionFloors
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();

            $InstitutionRoomsData = $InstitutionRooms
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();

            $InstitutionLandsData = $InstitutionLands
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();

            /****************POCOR-7326 Start********************* */
            $institutions = $Institutions->find('all')->toArray();
            $InsIds = [];
            foreach($InstitutionLandsData as $ke => $institutionLand){
                $InsIds[] = $institutionLand->institution_id;
            }
            //Check here Land entity for each school**
            $Unmatched =[];
            $Matched = [];
            foreach($institutions as $k => $Insti){ //echo "<pre>";print_r($land->institution_id);die;
                if (!in_array($Insti->id, $InsIds)) {
                    $Unmatched[$k] = $Insti->id;
                } else {
                    $Matched[$k] = $Insti->id;
                }
               
            }
            if(empty($Unmatched)){
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }else{
                //POCOR-7567 start
                $msg=$this->testInfrastructureData($entity->from_academic_period);
                if($msg!=""){
                    if($msg=="building"){
                            $this->Alert->warning('InstitutionBuildings.sizeGreater', ['reset' => true]);
                            return false;
                        
                    }
                    else if($msg=="floor"){
                            $this->Alert->warning('InstitutionFloors.sizeGreater', ['reset' => true]);
                            return false;
                        }
                    else if($msg=="room"){
                            $this->Alert->warning('InstitutionRooms.sizeGreater', ['reset' => true]);
                            return false;
                    }
                }
                //POCOR-7567 end
                $existRecord = $this->find('all',['conditions'=>[
                    'from_academic_period'=>$entity->from_academic_period,
                    'to_academic_period' => $entity->to_academic_period,
                    'features' => 'Infrastructure'
                ]])->first();
                if(!empty($existRecord)){
                    $this->delete($existRecord);
                }

            }
            //**********************POCOR-7326 End******************************* */
            if(!empty($InstitutionBuildingsData) && !empty($InstitutionFloorsData) && !empty($InstitutionRoomsData) && !empty($InstitutionLandsData)){
               // $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                //return false;
            }
        }
        // Start POCOR-5337
        $RiskData = TableRegistry::get('Institution.Risks');
        if($entity->features == 'Risks'){
            $RiskRecords = $RiskData
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();
            if(!empty($RiskRecords)){
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }
        }// End POCOR-5337
        if($entity->features == "Performance Competencies"){
            if($entity->from_academic_period == $entity->to_academic_period){
                $this->Alert->error('CopyData.genralerror', ['reset' => true]);
                return false;
            }
            $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
            $EducationSystems = TableRegistry::get('Education.EducationSystems');
            if($entity->to_academic_period){
                $ToAcademicPeriodsData = $AcademicPeriods
                ->find()
                ->select(['start_date', 'start_year','end_date'])
                ->where(['id' => $entity->to_academic_period])
                ->first();

                $CompetencyCriteriasTable = TableRegistry::get('Competency.CompetencyCriterias');
                $CompetencyTemplatesTable = TableRegistry::get('Competency.CompetencyTemplates');
                $CompetencyItemsTable = TableRegistry::get('Competency.CompetencyItems');

                $CompetencyCriteriasData = $CompetencyCriteriasTable
                    ->find('all')
                    ->where(['academic_period_id' => $entity->to_academic_period])
                    ->toArray();

                $CompetencyTemplatesData = $CompetencyTemplatesTable
                ->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])
                ->toArray();

                $CompetencyItemsData = $CompetencyItemsTable
                ->find('all')
                ->where(['academic_period_id' => $entity->to_academic_period])
                ->toArray();

                if(!empty($CompetencyCriteriasData) && !empty($CompetencyTemplatesData) && !empty($CompetencyItemsData)){
                    $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                    return false;
                }
                if(empty($CompetencyCriteriasData)){
                    $entity->competency_criterias_value = 0;
                }else{
                    $entity->competency_criterias_value = 1;
                }
                if(empty($CompetencyTemplatesData)){
                    $entity->competency_templates_value = 0;
                }else{
                    $entity->competency_templates_value = 1;
                }
                if(empty($CompetencyItemsData)){
                    $entity->competency_items_value = 0;
                }else{
                    $entity->competency_items_value = 1;
                }
            }
            if($entity->to_academic_period){
                
                $ToAcademicPeriodsData = $AcademicPeriods
                ->find()
                ->select(['start_date', 'start_year','end_date'])
                ->where(['id' => $entity->to_academic_period])
                ->first();

                $EducationSystemsdata = $EducationSystems
                    ->find('all')
                    ->where(['academic_period_id' => $entity->to_academic_period])
                    ->toArray();
                if(empty($EducationSystemsdata)){
                    $this->Alert->error('CopyData.nodataexisteducationsystem', ['reset' => true]);
                    return false;
                }
            }
        }
    }

    /***************POCOR-7326 Start*********************** */
    public function codeGenerateL($Inscode,$no){
        return $Inscode."-". $no;
        //return $Inscode."-". date('Ymdhis');
    }
    public function codeGenerateB($Inscode,$no,$b){
        return $Inscode."-". $no.$b;
        //return $Inscode."-". date('Ymdhis');
    }
    public function codeGenerateF($Inscode,$no,$b,$F){
        return $Inscode."-". $no.$b.$F;
        //return $Inscode."-". date('Ymdhis');
    }
    public function codeGenerateR($Inscode,$no,$b,$F,$R){
        return $Inscode."-". $no.$b.$F.$R;
        //return $Inscode."-". date('Ymdhis');
    }
    /*****************POCOR-7326 End************************** */

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){
     
        ini_set('memory_limit', '2G');
        $connection = ConnectionManager::get('default');
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $institution_program_grade_subjects = TableRegistry::get('institution_program_grade_subjects');
        $currentData = "'".date('Y-m-d H:i:s')."'";

        $from_academic_period = $entity->from_academic_period;
        $to_academic_period = $entity->to_academic_period;

        if($entity->features == "Institution Programmes, Grades and Subjects"){
            $InstitutionGradesdata = $InstitutionGrades
                ->find('all')
                ->toArray();
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $FromAcademicPeriodsData = $AcademicPeriods
                        ->find()
                        ->select(['start_date', 'start_year','id'])
                        ->where(['id' => $from_academic_period])
                        ->first();

            $ToAcademicPeriodsData = $AcademicPeriods
            ->find()
            ->select(['start_date', 'start_year','end_date'])
            ->where(['id' => $to_academic_period])
            ->first();

            // $InstitutionGradesdataToInsert = $InstitutionGrades
            // ->find('all')
            // ->where(['start_year' => $FromAcademicPeriodsData['start_year']])
            // ->toArray();

            $InstitutionGradesdatasToInsert = $InstitutionGrades
            ->find('all')
            ->where(['academic_period_id' =>  $from_academic_period])
            ->toArray();

            $InsIds = [];
            foreach($InstitutionGradesdatasToInsert as $ke => $ig_data){
                $InsIds[] = $ig_data->institution_id;
            }

            $Unmatched =[];
            $Matched = [];

            $institutions = $Institutions->find('all')->toArray();        
            // foreach($InstitutionGradesdataToInsert AS $InstitutionGradesdataValue){
                foreach($institutions as $k => $Insti){ 
                        $InstitutionGradesdataValue = $InstitutionGrades
                                                        ->find()
                                                        ->where(['academic_period_id' => $from_academic_period,'institution_id'=> $Insti->id])
                                                        ->toArray();
                        if(!empty($InstitutionGradesdataValue)){
                            foreach($InstitutionGradesdataValue as $key=>$newData){
                          
                                try{
                                    $statement = $connection->prepare('INSERT INTO institution_grades 
                                    (
                                                                    education_grade_id, 
                                                                    academic_period_id,
                                                                    start_date,
                                                                    start_year,
                                                                    end_date,
                                                                    end_year,
                                                                    institution_id,
                                                                    modified_user_id,
                                                                    modified,
                                                                    created_user_id,
                                                                    created)
                                                                    VALUES (:education_grade_id,
                                                                    :academic_period_id,
                                                                    :start_date, 
                                                                    :start_year,
                                                                    :end_date,
                                                                    :end_year,
                                                                    :institution_id,
                                                                    :modified_user_id,
                                                                    :modified,
                                                                    :created_user_id,
                                                                    :created)');
                
                                    $statement->execute([
                                    'education_grade_id' => $newData['education_grade_id'],
                                    'academic_period_id' => $to_academic_period,
                                    'start_date' => $ToAcademicPeriodsData['start_date']->format('Y-m-d'),
                                    'start_year' => $ToAcademicPeriodsData['start_year'],
                                    'end_date' => null,
                                    'end_year' => null,
                                    'institution_id' =>$newData['institution_id'],
                                    'modified_user_id' => 2,
                                    'modified' => date('Y-m-d H:i:s'),
                                    'created_user_id' => 2,
                                    'created' => date('Y-m-d H:i:s')
                                    ]);
                                
                                }catch (PDOException $e) {
                                    echo "<pre>";print_r($e);die;
                                }
                            } 
                        }

                }
            $from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
            $to_end_date = $ToAcademicPeriodsData['end_date']->format('Y-m-d');
            $to_start_year = $ToAcademicPeriodsData['start_year'];
            $from_start_date = "'".$from_start_date."'";
            $to_end_date = "'".$to_end_date."'";
            $final_from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
            $statement = $connection->prepare("SELECT education_systems.academic_period_id,correct_grade.id AS correct_grade_id,institution_grades.* FROM `institution_grades`
            INNER JOIN education_grades wrong_grade ON wrong_grade.id = institution_grades.education_grade_id
            INNER JOIN education_grades correct_grade ON correct_grade.code = wrong_grade.code
            INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
            INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
            INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
            INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
            LEFT JOIN academic_periods ON institution_grades.start_date BETWEEN $from_start_date AND $to_end_date
            AND academic_periods.academic_period_level_id != -1
            AND education_systems.academic_period_id = academic_periods.id
            WHERE correct_grade.id != institution_grades.education_grade_id AND academic_periods.id=$to_academic_period");

            $statement->execute();
            $row = $statement->fetchAll(\PDO::FETCH_ASSOC);
            foreach($row AS $rowData){
                $InstitutionGrades->updateAll(
                    ['education_grade_id' => $rowData['correct_grade_id']],    //field
                    ['education_grade_id' => $rowData['education_grade_id'], 'institution_id'=>$rowData['institution_id'],  'start_date' => $final_from_start_date, 'start_year' => $to_start_year]
                );
            }


            //to insert data in institution_program_grade_subjects[START]
            $conn = ConnectionManager::get('default');
            $queryData = "INSERT INTO `institution_program_grade_subjects` (`institution_grade_id`, `education_grade_id`, `education_grade_subject_id`, `institution_id`, `created_user_id`, `created`)
            SELECT subq3.new_inst_grade_id, subq3.new_ed_grade_id, subq2.subject_id, subq2.inst_id, '1', $currentData
            FROM (SELECT
                institutions.id institution_id,
                education_grades.id edu_grade_id,
                institution_grades.id old_institution_grade_id,
                institution_program_grade_subjects.institution_grade_id old_instit_grade_id,
                institution_program_grade_subjects.education_grade_subject_id subject_id,
                institution_program_grade_subjects.institution_id inst_id
            FROM institution_program_grade_subjects
            INNER JOIN institution_grades ON institution_grades.id = institution_program_grade_subjects.institution_grade_id
            INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
            INNER JOIN institutions ON institutions.id = institution_grades.institution_id
            INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
            INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
            INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
            INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
            INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
            WHERE academic_periods.id = $from_academic_period) subq2
        INNER JOIN (SELECT 
        subq.old_edu_grade_id old_ed_grade_id,
        subq1.new_edu_grade_id new_ed_grade_id,
        subq.old_institution_grade_id old_inst_grade_id,
        subq1.new_institution_grade_id new_inst_grade_id
        FROM(SELECT
            education_levels.name old_edu_level_name,
            education_cycles.name old_edu_cycle_name,
            education_programmes.code old_edu_programme_name,
            education_grades.id old_edu_grade_id,
            education_grades.code old_edu_grade_code,
            institution_grades.id old_institution_grade_id,
            institution_grades.institution_id old_institution_id
        FROM `institution_grades`
        INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
        INNER JOIN institutions ON institutions.id = institution_grades.institution_id
        INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
        INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
        INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
        INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
        INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
        WHERE academic_periods.id = $from_academic_period) subq
        INNER JOIN (SELECT 
            education_levels.name new_edu_level_name,
            education_cycles.name new_edu_cycle_name,
            education_programmes.code new_edu_programme_name,
            education_grades.id new_edu_grade_id,
            education_grades.code new_edu_grade_code,
            institution_grades.id new_institution_grade_id,
            institution_grades.institution_id new_institution_id
        FROM `institution_grades`
        INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
        INNER JOIN institutions ON institutions.id = institution_grades.institution_id
        INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
        INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
        INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
        INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
        INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
        WHERE academic_periods.id = $to_academic_period) subq1 ON subq1.new_edu_level_name = subq.old_edu_level_name AND subq1.new_edu_programme_name = subq.old_edu_programme_name AND subq1.new_edu_grade_code = subq.old_edu_grade_code AND subq1.new_edu_cycle_name = subq.old_edu_cycle_name AND subq1.new_institution_id = subq.old_institution_id) subq3 ON subq3.old_inst_grade_id = subq2.old_instit_grade_id";
            $conn->execute($queryData);
        }
        if($entity->features == "Shifts"){
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;
            $this->triggerCopyShell('Shift', $copyFrom, $copyTo);
        }
        
        if($entity->features == "Infrastructure"){
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;

            //***********************POCOR-7326 Start******************************* */    

            $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
            $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
            $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
            $Institutions = TableRegistry::get('Institution.Institutions');
            $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');

            $InstitutionLandsData = $InstitutionLands
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();



            $institutions = $Institutions->find('all')->toArray();
            $InsIds = [];
            foreach($InstitutionLandsData as $ke => $institutionLand){
                $InsIds[] = $institutionLand->institution_id;
            }
            //Check here Land entity for each school**
            $Unmatched =[];
            $Matched = [];
            foreach($institutions as $k => $Insti){ 
                if (!in_array($Insti->id, $InsIds)) {
                   $Unmatched[$k] = $Insti->id;
                   //*********Save Land/Bulding/Floor/room */
                   $InstitutionLandDataa = $InstitutionLands
                   ->find('all')
                   ->where(['academic_period_id ' => $entity->from_academic_period,'institution_id'=> $Insti->id])
                   ->first();

                   $AcademicPeriod = $AcademicPeriods->get($entity->to_academic_period);

                    $newLandEntity = $InstitutionLands->newEntity([
                        'code'=> $this->codeGenerateL($Insti->code,$k+1),
                        'name'=> $InstitutionLandDataa->name,
                        'start_date' => $AcademicPeriod->start_date,
                        'start_year' => $AcademicPeriod->start_year,
                        'end_date' => $AcademicPeriod->end_date,
                        'end_year' => $AcademicPeriod->end_year,
                        'year_acquired'=> $InstitutionLandDataa->year_acquired,
                        'year_disposed' => $InstitutionLandDataa->year_disposed,
                        'area' => $InstitutionLandDataa->area,
                        'accessibility'=> $InstitutionLandDataa->accessibility,
                        'comment'=> $InstitutionLandDataa->comment,
                        'institution_id'=> $InstitutionLandDataa->institution_id,
                        'academic_period_id'=> $AcademicPeriod->id,
                        'land_type_id'=> $InstitutionLandDataa->land_type_id,
                        'land_status_id'=> $InstitutionLandDataa->land_status_id,
                        'infrastructure_ownership_id'=> $InstitutionLandDataa->infrastructure_ownership_id,
                        'infrastructure_condition_id'=> $InstitutionLandDataa->infrastructure_condition_id,
                        'previous_institution_land_id'=> $InstitutionLandDataa->previous_institution_land_id,
                        'modified_user_id'=> $InstitutionLandDataa->modified_user_id,
                        'modified'=> $InstitutionLandDataa->modified,
                        'created_user_id'=> $InstitutionLandDataa->created_user_id,
                        'created'=> $InstitutionLandDataa->created,

                    ]);
                    if($saveLandEntity = $InstitutionLands->save($newLandEntity)){
                        
                        $InstitutionBuildingData = $InstitutionBuildings
                        ->find('all')
                        ->where(['institution_land_id ' => $InstitutionLandDataa->id])
                        ->toArray();
                        foreach($InstitutionBuildingData as $kei=> $building){
                            $newBuildingEntity = $InstitutionBuildings->newEntity([
                                'code'=>$this->codeGenerateB($Insti->code,$k+1,$kei+1),
                                'name'=>$building->name,
                                'start_date' => $AcademicPeriod->start_date,
                                'start_year' => $AcademicPeriod->start_year,
                                'end_date' => $AcademicPeriod->end_date,
                                'end_year' => $AcademicPeriod->end_year,
                                'year_acquired'=>$building->year_acquired,
                                'year_disposed'=>$building->year_disposed,
                                'area'=>$building->area,
                                'accessibility'=>$building->accessibility,
                                'comment'=>$building->comment,
                                'institution_land_id'=>$saveLandEntity->id,
                                'institution_id'=>$building->institution_id,
                                'academic_period_id'=>$AcademicPeriod->id,
                                'building_type_id'=>$building->building_type_id,
                                'building_status_id'=>$building->building_status_id,
                                'infrastructure_ownership_id'=>$building->infrastructure_ownership_id,

                                'infrastructure_condition_id'=>$building->infrastructure_condition_id,
                                'previous_institution_building_id'=>$building->previous_institution_building_id,
                                'modified_user_id'=>$building->modified_user_id,
                                'modified'=>$building->modified,
                                'created_user_id'=>$building->created_user_id,
                                'created'=>$building->created

                            ]);
                            
                            if($saveBuilding = $InstitutionBuildings->save($newBuildingEntity)){
                                $InstitutionFloorData = $InstitutionFloors
                                ->find('all')
                                ->where(['institution_building_id ' => $building->id])
                                ->toArray();

                                foreach($InstitutionFloorData as $kkey => $floor){
                                    $newFloorEntity = $InstitutionFloors->newEntity([

                                        'code'=>$this->codeGenerateF($Insti->code,$k+1,$kei+1,$kkey+1),
                                        'name'=>$floor->name,
                                        'start_date' => $AcademicPeriod->start_date,
                                        'start_year' => $AcademicPeriod->start_year,
                                        'end_date' => $AcademicPeriod->end_date,
                                        'end_year' => $AcademicPeriod->end_year,
                                    
                                        'area'=>$floor->area,
                                        'accessibility'=>$floor->accessibility,
                                        'comment'=>$floor->comment,
                                        'institution_building_id'=>$saveBuilding->id,
                                        'institution_id'=>$floor->institution_id,
                                        'academic_period_id'=>$AcademicPeriod->id,
                                        'floor_type_id'=>$floor->floor_type_id,
                                        'floor_status_id'=>$floor->floor_status_id,
                                        
                                        'infrastructure_condition_id'=>$floor->infrastructure_condition_id,
                                        'previous_institution_floor_id'=>$floor->previous_institution_floor_id,
                                        'modified_user_id'=>$floor->modified_user_id,
                                        'modified'=>$floor->modified,
                                        'created_user_id'=>$floor->created_user_id,
                                        'created'=>$floor->created

                                    ]);
                                   
                                    if($saveFloor = $InstitutionFloors->save($newFloorEntity)){
                                        
                                        $InstitutionRoomData = $InstitutionRooms
                                        ->find('all')
                                        ->where(['institution_floor_id ' => $floor->id])
                                        ->toArray();

                                        foreach($InstitutionRoomData as $no=>$room){
                                          $newRoomEntity = $InstitutionRooms->newEntity([
                                                'code'=>$this->codeGenerateR($Insti->code,$k+1,$kei+1,$kkey+1,$no+1),
                                                'name'=>$room->name,
                                                'start_date' => $AcademicPeriod->start_date,
                                                'start_year' => $AcademicPeriod->start_year,
                                                'end_date' => $AcademicPeriod->end_date,
                                                'end_year' => $AcademicPeriod->end_year,
                                            
                                            
                                                'accessibility'=>$room->accessibility,
                                                'comment'=>$room->comment,
                                            
                                                
                                                'room_type_id'=>$room->room_type_id,
                                                'room_status_id'=>$room->room_status_id,
                                                'institution_floor_id'=>$saveFloor->id,

                                                'institution_id'=>$room->institution_id,
                                                'academic_period_id'=>$AcademicPeriod->id,

                                                'infrastructure_condition_id'=>$room->infrastructure_condition_id,
                                                'area'=>$room->area,
                                                'previous_institution_room_id'=>$room->previous_institution_room_id,
                                                'modified_user_id'=>$room->modified_user_id,
                                                'modified'=>$room->modified,
                                                'created_user_id'=>$room->created_user_id,
                                                'created'=>$room->created
                                            ]);
                                            
                                            $InstitutionRooms->save($newRoomEntity);
                                        }
                                    }
                                }
                            }
                        
                        }

                    };


                } else {
                    $Matched[$k] = $Insti->id;
                }
               
            }
            if(!empty($Unmatched)){
                $this->Alert->success('CopyData.updatedRecord', ['reset' => true]);
                return false;
            }elseif(!empty($Matched)){
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }
            
            //**************************POCOR-7326 End************************************** */


            $this->triggerCopyShell('Infrastructure', $copyFrom, $copyTo);
        }

        // Start POCOR-5337
        if($entity->features == "Risks"){
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;
            $this->triggerCopyShell('Risk', $copyFrom, $copyTo);
        }
        // End POCOR-5337
    }
    
    // public function afterSave(Event $event, Entity $entity, ArrayObject $data){
    //     ini_set('memory_limit', '2G');
    //     $connection = ConnectionManager::get('default');
    //     $EducationSystems = TableRegistry::get('Education.EducationSystems');
    //     $EducationLevels = TableRegistry::get('Education.EducationLevels');
    //     $EducationCycles = TableRegistry::get('Education.EducationCycles');
    //     $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
    //     $EducationGrades = TableRegistry::get('Education.EducationGrades');
    //     $Institutions = TableRegistry::get('Institution.Institutions');
    //     $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
    //     $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
    //     $institution_program_grade_subjects = TableRegistry::get('institution_program_grade_subjects');
    //     $currentData = "'".date('Y-m-d H:i:s')."'";

    //     $from_academic_period = $entity->from_academic_period;
    //     $to_academic_period = $entity->to_academic_period;

    //     if($entity->features == "Institution Programmes, Grades and Subjects"){
    //         $InstitutionGradesdata = $InstitutionGrades
    //             ->find('all')
    //             ->toArray();
    //         $from_academic_period = $entity->from_academic_period;
    //         $to_academic_period = $entity->to_academic_period;
    //         $FromAcademicPeriodsData = $AcademicPeriods
    //                     ->find()
    //                     ->select(['start_date', 'start_year'])
    //                     ->where(['id' => $from_academic_period])
    //                     ->first();

    //         $ToAcademicPeriodsData = $AcademicPeriods
    //         ->find()
    //         ->select(['start_date', 'start_year','end_date'])
    //         ->where(['id' => $to_academic_period])
    //         ->first();

    //         $InstitutionGradesdataToInsert = $InstitutionGrades
    //         ->find('all')
    //         ->where(['start_year' => $FromAcademicPeriodsData['start_year']])
    //         ->toArray();
        
    //         foreach($InstitutionGradesdataToInsert AS $InstitutionGradesdataValue){
            
    //             try{
    //                 $statement = $connection->prepare('INSERT INTO institution_grades 
    //                 (
    //                 education_grade_id, 
    //                 academic_period_id,
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
    //                 :academic_period_id,
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
    //                 'education_grade_id' => $InstitutionGradesdataValue->education_grade_id,
    //                 'academic_period_id' => $to_academic_period,
    //                 'start_date' => $ToAcademicPeriodsData['start_date']->format('Y-m-d'),
    //                 'start_year' => $ToAcademicPeriodsData['start_year'],
    //                 'end_date' => null,
    //                 'end_year' => null,
    //                 'institution_id' => $InstitutionGradesdataValue->institution_id,
    //                 'modified_user_id' => 2,
    //                 'modified' => date('Y-m-d H:i:s'),
    //                 'created_user_id' => 2,
    //                 'created' => date('Y-m-d H:i:s')
    //                 ]);
                
    //             }catch (PDOException $e) {
    //                 echo "<pre>";print_r($e);die;
    //             }
    //         }
    //         //This code is for copy one academic period to onother[Start]
    //         $from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
    //         $to_end_date = $ToAcademicPeriodsData['end_date']->format('Y-m-d');
    //         $to_start_year = $ToAcademicPeriodsData['start_year'];
    //         $from_start_date = "'".$from_start_date."'";
    //         $to_end_date = "'".$to_end_date."'";
    //         $final_from_start_date = $ToAcademicPeriodsData['start_date']->format('Y-m-d');
    //         $statement = $connection->prepare("SELECT education_systems.academic_period_id,correct_grade.id AS correct_grade_id,institution_grades.* FROM `institution_grades`
    //         INNER JOIN education_grades wrong_grade ON wrong_grade.id = institution_grades.education_grade_id
    //         INNER JOIN education_grades correct_grade ON correct_grade.code = wrong_grade.code
    //         INNER JOIN education_programmes ON correct_grade.education_programme_id = education_programmes.id
    //         INNER JOIN education_cycles ON education_programmes.education_cycle_id = education_cycles.id
    //         INNER JOIN education_levels ON education_cycles.education_level_id = education_levels.id
    //         INNER JOIN education_systems ON education_levels.education_system_id = education_systems.id
    //         LEFT JOIN academic_periods ON institution_grades.start_date BETWEEN $from_start_date AND $to_end_date
    //         AND academic_periods.academic_period_level_id != -1
    //         AND education_systems.academic_period_id = academic_periods.id
    //         WHERE correct_grade.id != institution_grades.education_grade_id AND academic_periods.id=$to_academic_period");

    //         $statement->execute();
    //         $row = $statement->fetchAll(\PDO::FETCH_ASSOC);
    //         foreach($row AS $rowData){
    //             $InstitutionGrades->updateAll(
    //                 ['education_grade_id' => $rowData['correct_grade_id']],    //field
    //                 ['education_grade_id' => $rowData['education_grade_id'], 'institution_id'=>$rowData['institution_id'],  'start_date' => $final_from_start_date, 'start_year' => $to_start_year]
    //             );
    //         }


    //         //to insert data in institution_program_grade_subjects[START]
    //         $conn = ConnectionManager::get('default');
    //         $queryData = "INSERT INTO `institution_program_grade_subjects` (`institution_grade_id`, `education_grade_id`, `education_grade_subject_id`, `institution_id`, `created_user_id`, `created`)
    //         SELECT subq3.new_inst_grade_id, subq3.new_ed_grade_id, subq2.subject_id, subq2.inst_id, '1', $currentData
    //         FROM (SELECT
    //             institutions.id institution_id,
    //             education_grades.id edu_grade_id,
    //             institution_grades.id old_institution_grade_id,
    //             institution_program_grade_subjects.institution_grade_id old_instit_grade_id,
    //             institution_program_grade_subjects.education_grade_subject_id subject_id,
    //             institution_program_grade_subjects.institution_id inst_id
    //         FROM institution_program_grade_subjects
    //         INNER JOIN institution_grades ON institution_grades.id = institution_program_grade_subjects.institution_grade_id
    //         INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
    //         INNER JOIN institutions ON institutions.id = institution_grades.institution_id
    //         INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
    //         INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
    //         INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
    //         INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
    //         INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
    //         WHERE academic_periods.id = $from_academic_period) subq2
    //     INNER JOIN (SELECT 
    //     subq.old_edu_grade_id old_ed_grade_id,
    //     subq1.new_edu_grade_id new_ed_grade_id,
    //     subq.old_institution_grade_id old_inst_grade_id,
    //     subq1.new_institution_grade_id new_inst_grade_id
    //     FROM(SELECT
    //         education_levels.name old_edu_level_name,
    //         education_cycles.name old_edu_cycle_name,
    //         education_programmes.code old_edu_programme_name,
    //         education_grades.id old_edu_grade_id,
    //         education_grades.code old_edu_grade_code,
    //         institution_grades.id old_institution_grade_id,
    //         institution_grades.institution_id old_institution_id
    //     FROM `institution_grades`
    //     INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
    //     INNER JOIN institutions ON institutions.id = institution_grades.institution_id
    //     INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
    //     INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
    //     INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
    //     INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
    //     INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
    //     WHERE academic_periods.id = $from_academic_period) subq
    //     INNER JOIN (SELECT 
    //         education_levels.name new_edu_level_name,
    //         education_cycles.name new_edu_cycle_name,
    //         education_programmes.code new_edu_programme_name,
    //         education_grades.id new_edu_grade_id,
    //         education_grades.code new_edu_grade_code,
    //         institution_grades.id new_institution_grade_id,
    //         institution_grades.institution_id new_institution_id
    //     FROM `institution_grades`
    //     INNER JOIN education_grades ON education_grades.id = institution_grades.education_grade_id
    //     INNER JOIN institutions ON institutions.id = institution_grades.institution_id
    //     INNER JOIN education_programmes ON education_programmes.id = education_grades.education_programme_id
    //     INNER JOIN education_cycles ON education_cycles.id = education_programmes.education_cycle_id
    //     INNER JOIN education_levels ON education_levels.id = education_cycles.education_level_id
    //     INNER JOIN education_systems ON education_systems.id = education_levels.education_system_id
    //     INNER JOIN academic_periods ON academic_periods.id = education_systems.academic_period_id
    //     WHERE academic_periods.id = $to_academic_period) subq1 ON subq1.new_edu_level_name = subq.old_edu_level_name AND subq1.new_edu_programme_name = subq.old_edu_programme_name AND subq1.new_edu_grade_code = subq.old_edu_grade_code AND subq1.new_edu_cycle_name = subq.old_edu_cycle_name AND subq1.new_institution_id = subq.old_institution_id) subq3 ON subq3.old_inst_grade_id = subq2.old_instit_grade_id";
    //         $conn->execute($queryData);
    //     }
    //     if($entity->features == "Shifts"){
    //         $from_academic_period = $entity->from_academic_period;
    //         $to_academic_period = $entity->to_academic_period;
    //         $copyFrom = $from_academic_period;
    //         $copyTo = $to_academic_period;
    //         $this->triggerCopyShell('Shift', $copyFrom, $copyTo);
    //     }
        
    //     if($entity->features == "Infrastructure"){
    //         $from_academic_period = $entity->from_academic_period;
    //         $to_academic_period = $entity->to_academic_period;
    //         $copyFrom = $from_academic_period;
    //         $copyTo = $to_academic_period;

    //         //***********************POCOR-7326 Start******************************* */    

    //         $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
    //         $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
    //         $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
    //         $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
    //         $Institutions = TableRegistry::get('Institution.Institutions');
    //         $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');

    //         $InstitutionLandsData = $InstitutionLands
    //             ->find('all')
    //             ->where(['academic_period_id ' => $entity->to_academic_period])
    //             ->toArray();



    //         $institutions = $Institutions->find('all')->toArray();
    //         $InsIds = [];
    //         foreach($InstitutionLandsData as $ke => $institutionLand){
    //             $InsIds[] = $institutionLand->institution_id;
    //         }
    //         //Check here Land entity for each school**
    //         $Unmatched =[];
    //         $Matched = [];
    //         foreach($institutions as $k => $Insti){ 
    //             if (!in_array($Insti->id, $InsIds)) {
    //                $Unmatched[$k] = $Insti->id;
    //                //*********Save Land/Bulding/Floor/room */
    //                $InstitutionLandDataa = $InstitutionLands
    //                ->find('all')
    //                ->where(['academic_period_id ' => $entity->from_academic_period,'institution_id'=> $Insti->id])
    //                ->first();

    //                $AcademicPeriod = $AcademicPeriods->get($entity->to_academic_period);

    //                 $newLandEntity = $InstitutionLands->newEntity([
    //                     'code'=> $this->codeGenerateL($Insti->code,$k+1),
    //                     'name'=> $InstitutionLandDataa->name,
    //                     'start_date' => $AcademicPeriod->start_date,
    //                     'start_year' => $AcademicPeriod->start_year,
    //                     'end_date' => $AcademicPeriod->end_date,
    //                     'end_year' => $AcademicPeriod->end_year,
    //                     'year_acquired'=> $InstitutionLandDataa->year_acquired,
    //                     'year_disposed' => $InstitutionLandDataa->year_disposed,
    //                     'area' => $InstitutionLandDataa->area,
    //                     'accessibility'=> $InstitutionLandDataa->accessibility,
    //                     'comment'=> $InstitutionLandDataa->comment,
    //                     'institution_id'=> $InstitutionLandDataa->institution_id,
    //                     'academic_period_id'=> $AcademicPeriod->id,
    //                     'land_type_id'=> $InstitutionLandDataa->land_type_id,
    //                     'land_status_id'=> $InstitutionLandDataa->land_status_id,
    //                     'infrastructure_ownership_id'=> $InstitutionLandDataa->infrastructure_ownership_id,
    //                     'infrastructure_condition_id'=> $InstitutionLandDataa->infrastructure_condition_id,
    //                     'previous_institution_land_id'=> $InstitutionLandDataa->previous_institution_land_id,
    //                     'modified_user_id'=> $InstitutionLandDataa->modified_user_id,
    //                     'modified'=> $InstitutionLandDataa->modified,
    //                     'created_user_id'=> $InstitutionLandDataa->created_user_id,
    //                     'created'=> $InstitutionLandDataa->created,

    //                 ]);
                    
    //                 if($saveLandEntity = $InstitutionLands->save($newLandEntity)){
                        
    //                     $InstitutionBuildingData = $InstitutionBuildings
    //                     ->find('all')
    //                     ->where(['institution_land_id ' => $InstitutionLandDataa->id])
    //                     ->toArray();
    //                     foreach($InstitutionBuildingData as $kei=> $building){

    //                         $newBuildingEntity = $InstitutionBuildings->newEntity([
    //                             'code'=>$this->codeGenerateB($Insti->code,$k+1,$kei+1),
    //                             'name'=>$building->name,
    //                             'start_date' => $AcademicPeriod->start_date,
    //                             'start_year' => $AcademicPeriod->start_year,
    //                             'end_date' => $AcademicPeriod->end_date,
    //                             'end_year' => $AcademicPeriod->end_year,
    //                             'year_acquired'=>$building->year_acquired,
    //                             'year_disposed'=>$building->year_disposed,
    //                             'area'=>$building->area,
    //                             'accessibility'=>$building->accessibility,
    //                             'comment'=>$building->comment,
    //                             'institution_land_id'=>$saveLandEntity->id,
    //                             'institution_id'=>$building->institution_id,
    //                             'academic_period_id'=>$AcademicPeriod->id,
    //                             'building_type_id'=>$building->building_type_id,
    //                             'building_status_id'=>$building->building_status_id,
    //                             'infrastructure_ownership_id'=>$building->infrastructure_ownership_id,

    //                             'infrastructure_condition_id'=>$building->infrastructure_condition_id,
    //                             'previous_institution_building_id'=>$building->previous_institution_building_id,
    //                             'modified_user_id'=>$building->modified_user_id,
    //                             'modified'=>$building->modified,
    //                             'created_user_id'=>$building->created_user_id,
    //                             'created'=>$building->created

    //                         ]);

    //                         if($saveBuilding = $InstitutionBuildings->save($newBuildingEntity)){
    //                             $InstitutionFloorData = $InstitutionFloors
    //                             ->find('all')
    //                             ->where(['institution_building_id ' => $building->id])
    //                             ->toArray();

    //                             foreach($InstitutionFloorData as $kkey => $floor){
    //                                 $newFloorEntity = $InstitutionFloors->newEntity([

    //                                     'code'=>$this->codeGenerateF($Insti->code,$k+1,$kei+1,$kkey+1),
    //                                     'name'=>$floor->name,
    //                                     'start_date' => $AcademicPeriod->start_date,
    //                                     'start_year' => $AcademicPeriod->start_year,
    //                                     'end_date' => $AcademicPeriod->end_date,
    //                                     'end_year' => $AcademicPeriod->end_year,
                                    
    //                                     'area'=>$floor->area,
    //                                     'accessibility'=>$floor->accessibility,
    //                                     'comment'=>$floor->comment,
    //                                     'institution_building_id'=>$saveBuilding->id,
    //                                     'institution_id'=>$floor->institution_id,
    //                                     'academic_period_id'=>$AcademicPeriod->id,
    //                                     'floor_type_id'=>$floor->floor_type_id,
    //                                     'floor_status_id'=>$floor->floor_status_id,
                                        
    //                                     'infrastructure_condition_id'=>$floor->infrastructure_condition_id,
    //                                     'previous_institution_floor_id'=>$floor->previous_institution_floor_id,
    //                                     'modified_user_id'=>$floor->modified_user_id,
    //                                     'modified'=>$floor->modified,
    //                                     'created_user_id'=>$floor->created_user_id,
    //                                     'created'=>$floor->created

    //                                 ]);
                                   
    //                                 if($saveFloor = $InstitutionFloors->save($newFloorEntity)){
                                        
    //                                     $InstitutionRoomData = $InstitutionRooms
    //                                     ->find('all')
    //                                     ->where(['institution_floor_id ' => $floor->id])
    //                                     ->toArray();

    //                                     foreach($InstitutionRoomData as $no=>$room){
    //                                         $newRoomEntity = $InstitutionRooms->newEntity([
    //                                             'code'=>$this->codeGenerateR($Insti->code,$k+1,$kei+1,$kkey+1,$no+1),
    //                                             'name'=>$room->name,
    //                                             'start_date' => $AcademicPeriod->start_date,
    //                                             'start_year' => $AcademicPeriod->start_year,
    //                                             'end_date' => $AcademicPeriod->end_date,
    //                                             'end_year' => $AcademicPeriod->end_year,
                                            
                                            
    //                                             'accessibility'=>$room->accessibility,
    //                                             'comment'=>$room->comment,
                                            
                                                
    //                                             'room_type_id'=>$room->room_type_id,
    //                                             'room_status_id'=>$room->room_status_id,
    //                                             'institution_floor_id'=>$saveFloor->id,

    //                                             'institution_id'=>$room->institution_id,
    //                                             'academic_period_id'=>$AcademicPeriod->id,

    //                                             'infrastructure_condition_id'=>$room->infrastructure_condition_id,
    //                                             'area'=>$room->area,
    //                                             'previous_institution_room_id'=>$room->previous_institution_room_id,
    //                                             'modified_user_id'=>$room->modified_user_id,
    //                                             'modified'=>$room->modified,
    //                                             'created_user_id'=>$room->created_user_id,
    //                                             'created'=>$room->created
    //                                         ]);
                                            
    //                                         $InstitutionRooms->save($newRoomEntity);
    //                                     }
    //                                 }
    //                             }
    //                         }
                        
    //                     }

    //                 };


    //             } else {
    //                 $Matched[$k] = $Insti->id;
    //             }
               
    //         }
    //         if(!empty($Unmatched)){
    //             $this->Alert->success('CopyData.updatedRecord', ['reset' => true]);
    //             return false;
    //         }elseif(!empty($Matched)){
    //             $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
    //             return false;
    //         }


    //         $this->triggerCopyShell('Infrastructure', $copyFrom, $copyTo);
    //     }

    //     // Start POCOR-5337
    //     if($entity->features == "Risks"){
    //         $from_academic_period = $entity->from_academic_period;
    //         $to_academic_period = $entity->to_academic_period;
    //         $copyFrom = $from_academic_period;
    //         $copyTo = $to_academic_period;
    //         $this->triggerCopyShell('Risk', $copyFrom, $copyTo);
    //     }
    //     // End POCOR-5337

        
    //     if($entity->features == "Performance Competencies"){
    //         $this->log('=======>Before triggerPerformanceCompetenciesShell', 'debug');
    //         $this->triggePerformanceCompetenciesShell('PerformanceCompetencies',$entity->from_academic_period, $entity->to_academic_period, $entity->competency_criterias_value, $entity->competency_templates_value, $entity->competency_items_value);
    //         $this->log(' <<<<<<<<<<======== After triggerPerformanceCompetenciesShell', 'debug');
    //     }
    // }

     /*
    * Function to copy Shift and Infrastucture from old academic period to new academic period
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-6825
    */

    public function triggerCopyShell($shellName, $copyFrom, $copyTo)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$copyFrom.' '.$copyTo;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'_copy.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }


    public function getFeatureOptions(){
        $options = [
            'Education Structure' => __('Education Structure'),
            'Institution Programmes, Grades and Subjects' => __('Institution Programmes, Grades and Subjects'),
            'Shifts' => __('Shifts'),
            'Infrastructure' => __('Infrastructure'),
            'Risks' => __('Risks'), // POCOR-5337
            'Performance Competencies' => __('Performance Competencies')
        ];
        return $options;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetToAcademicPeriod(Event $event, Entity $entity)
    {
        $AcademicPeriodsData = TableRegistry::get('Academic.AcademicPeriods');
        $result = $AcademicPeriodsData
            ->find()
            ->select(['name'])
            ->where(['id' => $entity->to_academic_period])
            ->first();

        return $entity->to_academic_period = $result->name;
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->generated_by])
            ->first();

        return $entity->generated_by = $result->first_name.' '.$result->last_name;
    }
    /*
    * Function to copy competency_criterias, competency_templates and competency_items to new academic period
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return boolean
    * @ticket POCOR-6424
    */
    
    public function triggePerformanceCompetenciesShell($shellName, $from_academic_period, $to_academic_period = null, $competency_criterias_value = null, $competency_templates_value = null, $competency_items_value = null)
    {
        $args = '';
        $args .= !is_null($from_academic_period) ? ' '.$from_academic_period : '';
        $args .= !is_null($to_academic_period) ? ' '.$to_academic_period : '';
        $args .= !is_null($competency_criterias_value) ? ' '.$competency_criterias_value : '';
        $args .= !is_null($competency_templates_value) ? ' '.$competency_templates_value : '';
        $args .= !is_null($competency_items_value) ? ' '.$competency_items_value : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
     }
    //POCOR-7576-shifts start
    private function checkshiftCopiedData( $copyFrom,$copyTo)
    {
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $copiedRecords = $InstitutionShifts->find()
                        ->innerJoin(
                                    ['InstitutionShifts1' => 'institution_shifts'],
                                    [
                                        $InstitutionShifts->aliasField('institution_id') . ' = InstitutionShifts1.institution_id',
                                        $InstitutionShifts->aliasField('location_institution_id') . ' = InstitutionShifts1.location_institution_id',
                                        $InstitutionShifts->aliasField('shift_option_id') . ' = InstitutionShifts1.shift_option_id',
                                        $InstitutionShifts->aliasField('start_time') . ' = InstitutionShifts1.start_time',
                                        $InstitutionShifts->aliasField('end_time') . ' = InstitutionShifts1.end_time'
                                    ]
                        )
                        ->where([
                                    $InstitutionShifts->aliasField('academic_period_id') => $copyFrom,
                                    'InstitutionShifts1.academic_period_id' => $copyTo,
                        ])
                        ->count();
           
        $allRecords= $InstitutionShifts->find()
                                  ->where([$InstitutionShifts->aliasField('academic_period_id') => $copyFrom])
                                  ->count();
        if($copiedRecords<$allRecords){
                return false;
        }
        return true;
    }
    
     //POCOR-7576-shifts end
    //POCOR-7576-institution programme start
    private function checkInstitutionCopiedData($copyFrom,$copyTo){
        $educationGradesTable = TableRegistry::get('Education.EducationGrades');
        $institutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');


        $query = $institutionGradesTable
        ->find()
        ->select([
            'period_id' => 'AcademicPeriods.id',
            'period_name' => 'AcademicPeriods.name',
            'period_code' => 'AcademicPeriods.code',
            'grade_id' => 'EducationGrades.id',
            'grade_name' => 'EducationGrades.name',
            'programme_name' => 'EducationProgrammes.name',
            'institution_id' => 'Institutions.id'
        ])
        ->innerJoin(
            ['EducationGrades' => 'education_grades'],
            ['EducationGrades.id = InstitutionGrades.education_grade_id']
        )
        ->innerJoin(
            ['Institutions' => 'institutions'],
            ['Institutions.id = InstitutionGrades.institution_id']
        )
        ->innerJoin(
            ['EducationProgrammes' => 'education_programmes'],
            ['EducationGrades.education_programme_id = EducationProgrammes.id']
        )
        ->innerJoin(
            ['EducationCycles' => 'education_cycles'],
            ['EducationProgrammes.education_cycle_id = EducationCycles.id']
        )
        ->innerJoin(
            ['EducationLevels' => 'education_levels'],
            ['EducationCycles.education_level_id = EducationLevels.id']
        )
        ->innerJoin(
            ['EducationSystems' => 'education_systems'],
            ['EducationLevels.education_system_id = EducationSystems.id']
        )
        ->innerJoin(
            ['AcademicPeriods' => 'academic_periods'],
            ['EducationSystems.academic_period_id = AcademicPeriods.id']
        )
        ->order([
            'AcademicPeriods.order' => 'ASC',
            'EducationLevels.order' => 'ASC',
            'EducationCycles.order' => 'ASC',
            'EducationProgrammes.order' => 'ASC',
            'EducationGrades.order' => 'ASC',
            'Institutions.id' => 'ASC'
        ]);
        $copyFromData = $this->filter_array($query,$copyFrom,'period_id');
        $copyToData = $this->filter_array($query,$copyTo,'period_id');
        $insIds=array_unique(array_column($copyFromData, 'institution_id'));
        $count=0;
  
        foreach($insIds as $val){

            $data1 = array_filter($copyFromData, function ($value, $key) use ($val) {
                return $value['institution_id'] == $val ;
            }, ARRAY_FILTER_USE_BOTH);
            $data2 = array_filter($copyToData, function ($value, $key) use ($val) {
                return $value['institution_id'] == $val ;
            }, ARRAY_FILTER_USE_BOTH);
            if(count($data1)>count($data2)){
               $count=$count+(count($data1)-count($data2));
            }
    
        }
        if($count>0){
           return false;
        }
        return true;
        
   }
 
    public function filter_array($array,$term,$column){
        $matches = array();
        foreach($array as $a){
            if($a[$column] == $term)
                $matches[]=$a;
        }
        return $matches;
    }
    //POCOR-7576-institution programme end 
    private function testInfrastructureData($copyFrom){
    
        $msg="";
            $InstitutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
            $InstitutionFloors = TableRegistry::get('Institution.InstitutionFloors');
            $InstitutionRooms = TableRegistry::get('Institution.InstitutionRooms');
            $InstitutionLands = TableRegistry::get('Institution.InstitutionLands');
            $Institutions = TableRegistry::get('Institution.Institutions');
            $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');

            $InstitutionLandsData = $InstitutionLands
                ->find('all')
                ->where(['academic_period_id ' => $copyFrom])
                ->toArray();
            $institutions = $Institutions->find('all')->toArray();
            $InsIds = [];
            foreach($InstitutionLandsData as $ke => $institutionLand){
                $InsIds[] = $institutionLand->institution_id;
            }
            foreach($institutions as $k => $Insti){ 
             
                if (in_array($Insti->id, $InsIds)) {
                   $InstitutionLandDataa = $InstitutionLands
                   ->find('all')
                   ->where(['academic_period_id ' => $copyFrom,'institution_id'=> $Insti->id])
                   ->first();
                        $InstitutionBuildingData = $InstitutionBuildings
                        ->find('all')
                        ->where(['institution_land_id ' => $InstitutionLandDataa->id])
                        ->toArray();
                        foreach($InstitutionBuildingData as $kei=> $building){
                            if($building->area >= $InstitutionLandDataa->area){//POOR-7567
                               $msg="building";
                               return $msg;
                            }
                                $InstitutionFloorData = $InstitutionFloors
                                ->find('all')
                                ->where(['institution_building_id ' => $building->id])
                                ->toArray();

                                foreach($InstitutionFloorData as $kkey => $floor){
                                    if($floor->area >= $building->area){//POCOR-7567
                                        $msg="floor";
                                        return $msg;
                                    }
                                        
                                        $InstitutionRoomData = $InstitutionRooms
                                        ->find('all')
                                        ->where(['institution_floor_id ' => $floor->id])
                                        ->toArray();

                                        foreach($InstitutionRoomData as $no=>$room){
                                            if($room->area >= $floor->area){//POCOR-7567
                                                $msg="room";
                                                return $msg;
                                            }
                                         }
                                }
                        }
                    }
            }
       
       return $msg;
    }
}