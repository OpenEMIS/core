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
 * @method \Archive\Model\Entity\DataManagementCopy get($primaryKey, $options = [])
 * @method \Archive\Model\Entity\DataManagementCopy newEntity($data = null, array $options = [])
 * @method \Archive\Model\Entity\DataManagementCopy[] newEntities(array $data, array $options = [])
 * @method \Archive\Model\Entity\DataManagementCopy|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Archive\Model\Entity\DataManagementCopy patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Archive\Model\Entity\DataManagementCopy[] patchEntities($entities, array $data, array $options = [])
 * @method \Archive\Model\Entity\DataManagementCopy findOrCreate($search, callable $callback = null, $options = [])
 */

class DataManagementCopyTable extends ControllerActionTable
{
    use MessagesTrait;
    //POCOR-7924:start
    const REPORT_CARDS = 'Report Card Templates';
    const EDUCATION_STRUCTURE = 'Education Structure';
    const INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS = 'Institution Programmes, Grades and Subjects';
    const SHIFTS = 'Shifts';
    const INFRASTRUCTURE = 'Infrastructure';
    const RISKS = 'Risks';
    const PERFORMANCE_COMPETENCIES = 'Performance Competencies';
    const PERFORMANCE_ASSESSMENTS = 'Performance Assessments';
    const PERFORMANCE_OUTCOMES = 'Institution Performance Outcomes';
    //POCOR-7924:end

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
            //POCOR-7568 start
            if($entity->features == self::EDUCATION_STRUCTURE){
                    if(!empty($EducationSystemsdata)){
                        $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);//if education structure data already exist
                        return false;
                    }
            }
            else{ //POCOR-7568 end
            if(empty($EducationSystemsdata)){
                $this->Alert->error('CopyData.nodataexisteducationsystem', ['reset' => true]);
                return false;
            }
            }
        }
        if($entity->features == self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS){
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
        if($entity->features == self::SHIFTS){
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
        if($entity->features == self::INFRASTRUCTURE){
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
                $existRecord = $this->find('all',['conditions'=>[
                    'from_academic_period'=>$entity->from_academic_period,
                    'to_academic_period' => $entity->to_academic_period,
                    'features' => self::INFRASTRUCTURE
                ]])->first();
                if(!empty($existRecord)){
                    $this->delete($existRecord);
                }

            }
            //**********************POCOR-7326 End******************************* */
            if(!empty($InstitutionBuildingsData)
                && !empty($InstitutionFloorsData)
                && !empty($InstitutionRoomsData)
                && !empty($InstitutionLandsData)){
               // $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                //return false;
            }
        }
        // Start POCOR-5337
        $RiskData = TableRegistry::get('Institution.Risks');
        if($entity->features == self::RISKS){
            $RiskRecords = $RiskData
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();
            if(!empty($RiskRecords)){
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }
        }// End POCOR-5337
        if($entity->features == self::PERFORMANCE_COMPETENCIES){
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
        // Start POCOR-6423
        $AssessmentData = TableRegistry::get('Assessment.Assessments');
        if ($entity->features == self::PERFORMANCE_ASSESSMENTS) {
            $AssessmentRecords = $AssessmentData
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->count();
            $PreviousAssessmentRecords = $AssessmentData
                ->find('all')
                ->where(['academic_period_id ' => $entity->from_academic_period])
                ->count();
            if($AssessmentRecords>= $PreviousAssessmentRecords) {
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }
        }
        // End POCOR-6423
        // POCOR-7764-start
        $ReportCard = TableRegistry::get('ReportCard.ReportCards');
        if ($entity->features == self::REPORT_CARDS) {
            $ReportCardData = $ReportCard->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->toArray();
            if (!empty($ReportCardData)) {
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }
        }
        // POCOR-7764-end
    }

    /***************POCOR-7326 Start*********************** */
    public function codeGenerateL($Inscode, $no){
        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
        return $Inscode."-" . $no;
        //return $Inscode."-". date('Ymdhis');
    }
    public function codeGenerateB($Inscode,$no,$b){
        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
        $b = str_pad($b, 2, 0, STR_PAD_LEFT);
        return $Inscode."-". $no.$b;
        //return $Inscode."-". date('Ymdhis');
    }
    public function codeGenerateF($Inscode,$no,$b,$F){
        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
        $b = str_pad($b, 2, 0, STR_PAD_LEFT);
        $F = str_pad($F, 2, 0, STR_PAD_LEFT);
        return $Inscode."-". $no.$b.$F;
        //return $Inscode."-". date('Ymdhis');
    }
    public function codeGenerateR($Inscode,$no,$b,$F,$R){
        $no = str_pad($no, 2, 0, STR_PAD_LEFT);
        $b = str_pad($b, 2, 0, STR_PAD_LEFT);
        $F = str_pad($F, 2, 0, STR_PAD_LEFT);
        $R = str_pad($R, 2, 0, STR_PAD_LEFT);
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

        if($entity->features == self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS){
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;
            $this->triggerCopyShell('InstitutionProgramAndGrade', $copyFrom, $copyTo);
        }
        if($entity->features == self::SHIFTS){
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;
            $this->triggerCopyShell('Shift', $copyFrom, $copyTo);
        }
        
        if($entity->features == self::INFRASTRUCTURE){
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
            foreach($institutions as $institutionKey => $Institution){ 
                if (!in_array($Institution->id, $InsIds)) {
                   $Unmatched[$institutionKey] = $Institution->id;
                   //*********Save Land/Bulding/Floor/room */
                   $oldLand = $InstitutionLands
                   ->find('all')
                   ->where(['academic_period_id ' => $entity->from_academic_period,
                       'institution_id'=> $Institution->id])
                   ->first();

                   $AcademicPeriod = $AcademicPeriods->get($entity->to_academic_period);

                   $newLand = $InstitutionLands->newEntity([
                        'code'=> $this->codeGenerateL($Institution->code,$institutionKey+1),
                        'name'=> $oldLand->name,
                        'start_date' => $AcademicPeriod->start_date,
                        'start_year' => $AcademicPeriod->start_year,
                        'end_date' => $AcademicPeriod->end_date,
                        'end_year' => $AcademicPeriod->end_year,
                        'year_acquired'=> $oldLand->year_acquired,
                        'year_disposed' => $oldLand->year_disposed,
                        'area' => $oldLand->area,
                        'accessibility'=> $oldLand->accessibility,
                        'comment'=> $oldLand->comment,
                        'institution_id'=> $oldLand->institution_id,
                        'academic_period_id'=> $AcademicPeriod->id,
                        'land_type_id'=> $oldLand->land_type_id,
                        'land_status_id'=> $oldLand->land_status_id,
                        'infrastructure_ownership_id'=> $oldLand->infrastructure_ownership_id,
                        'infrastructure_condition_id'=> $oldLand->infrastructure_condition_id,
                        'previous_institution_land_id'=> $oldLand->previous_institution_land_id,
                        'modified_user_id'=> $oldLand->modified_user_id,
                        'modified'=> $oldLand->modified,
                        'created_user_id'=> $oldLand->created_user_id,
                        'created'=> $oldLand->created,

                    ]);
                    if($saveLand = $InstitutionLands->save($newLand)){
                        $oldLandId = $oldLand->id;
                        $newLandId = $saveLand->id;
                        $tableName = "land_custom_field_values";
                        $fieldName = "institution_land_id";
                        $this->copyCustomFields($connection, $tableName, $fieldName, $newLandId, $oldLandId);
                        $InstitutionBuildingData = $InstitutionBuildings
                        ->find('all')
                        ->where(['institution_land_id ' => $oldLand->id])
                        ->toArray();
                        foreach($InstitutionBuildingData as $buildingKey=> $oldBuilding){
                            $newBuildingEntity = $InstitutionBuildings->newEntity([
                                'code'=>$this->codeGenerateB($Institution->code,$institutionKey+1,$buildingKey+1),
                                'name'=>$oldBuilding->name,
                                'start_date' => $AcademicPeriod->start_date,
                                'start_year' => $AcademicPeriod->start_year,
                                'end_date' => $AcademicPeriod->end_date,
                                'end_year' => $AcademicPeriod->end_year,
                                'year_acquired'=>$oldBuilding->year_acquired,
                                'year_disposed'=>$oldBuilding->year_disposed,
                                'area'=>$oldBuilding->area,
                                'accessibility'=>$oldBuilding->accessibility,
                                'comment'=>$oldBuilding->comment,
                                'institution_land_id'=>$newLandId,
                                'institution_id'=>$oldBuilding->institution_id,
                                'academic_period_id'=>$AcademicPeriod->id,
                                'building_type_id'=>$oldBuilding->building_type_id,
                                'building_status_id'=>$oldBuilding->building_status_id,
                                'infrastructure_ownership_id'=>$oldBuilding->infrastructure_ownership_id,

                                'infrastructure_condition_id'=>$oldBuilding->infrastructure_condition_id,
                                'previous_institution_building_id'=>$oldBuilding->previous_institution_building_id,
                                'modified_user_id'=>$oldBuilding->modified_user_id,
                                'modified'=>$oldBuilding->modified,
                                'created_user_id'=>$oldBuilding->created_user_id,
                                'created'=>$oldBuilding->created

                            ]);
                            
                            if($saveBuilding = $InstitutionBuildings->save($newBuildingEntity)){
                                $oldBuildingId = $oldBuilding->id;
                                $newBuildingId = $saveBuilding->id;
                                $tableName = "building_custom_field_values";
                                $fieldName = "institution_building_id";
                                $this->copyCustomFields($connection, $tableName, $fieldName, $newBuildingId, $oldBuildingId);
                                $InstitutionFloorData = $InstitutionFloors
                                ->find('all')
                                ->where(['institution_building_id ' => $oldBuilding->id])
                                ->toArray();

                                foreach($InstitutionFloorData as $floorKey => $oldFloor){
                                    $newFloor = $InstitutionFloors->newEntity([

                                        'code'=>$this->codeGenerateF($Institution->code,
                                            $institutionKey+1,
                                            $buildingKey+1,
                                            $floorKey+1),
                                        'name'=>$oldFloor->name,
                                        'start_date' => $AcademicPeriod->start_date,
                                        'start_year' => $AcademicPeriod->start_year,
                                        'end_date' => $AcademicPeriod->end_date,
                                        'end_year' => $AcademicPeriod->end_year,
                                    
                                        'area'=>$oldFloor->area,
                                        'accessibility'=>$oldFloor->accessibility,
                                        'comment'=>$oldFloor->comment,
                                        'institution_building_id'=>$saveBuilding->id,
                                        'institution_id'=>$oldFloor->institution_id,
                                        'academic_period_id'=>$AcademicPeriod->id,
                                        'floor_type_id'=>$oldFloor->floor_type_id,
                                        'floor_status_id'=>$oldFloor->floor_status_id,
                                        
                                        'infrastructure_condition_id'=>$oldFloor->infrastructure_condition_id,
                                        'previous_institution_floor_id'=>$oldFloor->previous_institution_floor_id,
                                        'modified_user_id'=>$oldFloor->modified_user_id,
                                        'modified'=>$oldFloor->modified,
                                        'created_user_id'=>$oldFloor->created_user_id,
                                        'created'=>$oldFloor->created

                                    ]);
                                   
                                    if($saveFloor = $InstitutionFloors->save($newFloor)){
                                        $oldFloorId = $oldFloor->id;
                                        $newFloorId = $saveFloor->id;
                                        $tableName = "floor_custom_field_values";
                                        $fieldName = "institution_floor_id";
                                        $this->copyCustomFields($connection, $tableName, $fieldName, $newFloorId, $oldFloorId);

                                        $InstitutionRoomData = $InstitutionRooms
                                        ->find('all')
                                        ->where(['institution_floor_id ' => $oldFloor->id])
                                        ->toArray();

                                        foreach($InstitutionRoomData as $roomKey=>$oldRoom){
                                          $newRoom = $InstitutionRooms->newEntity([
                                                'code'=>$this->codeGenerateR($Institution->code,
                                                    $institutionKey+1,
                                                    $buildingKey+1,
                                                    $floorKey+1,
                                                    $roomKey+1),
                                                'name'=>$oldRoom->name,
                                                'start_date' => $AcademicPeriod->start_date,
                                                'start_year' => $AcademicPeriod->start_year,
                                                'end_date' => $AcademicPeriod->end_date,
                                                'end_year' => $AcademicPeriod->end_year,
                                            
                                            
                                                'accessibility'=>$oldRoom->accessibility,
                                                'comment'=>$oldRoom->comment,
                                            
                                                
                                                'room_type_id'=>$oldRoom->room_type_id,
                                                'room_status_id'=>$oldRoom->room_status_id,
                                                'institution_floor_id'=>$saveFloor->id,

                                                'institution_id'=>$oldRoom->institution_id,
                                                'academic_period_id'=>$AcademicPeriod->id,

                                                'infrastructure_condition_id'=>$oldRoom->infrastructure_condition_id,
                                                'area'=>$oldRoom->area,
                                                'previous_institution_room_id'=>$oldRoom->previous_institution_room_id,
                                                'modified_user_id'=>$oldRoom->modified_user_id,
                                                'modified'=>$oldRoom->modified,
                                                'created_user_id'=>$oldRoom->created_user_id,
                                                'created'=>$oldRoom->created
                                            ]);
                                            if($saveRoom = $InstitutionRooms->save($newRoom)){
                                                $oldRoomId = $oldRoom->id;
                                                $newRoomId = $saveRoom->id;
                                                $tableName = "room_custom_field_values";
                                                $fieldName = "institution_room_id";
                                                $this->copyCustomFields($connection, $tableName, $fieldName, $newRoomId, $oldRoomId);
                                            }

                                        }
                                    }
                                }
                            }
                        
                        }

                    };


                } else {
                    $Matched[$institutionKey] = $Institution->id;
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


            $this->triggerCopyShell(self::INFRASTRUCTURE, $copyFrom, $copyTo);
        }

        // Start POCOR-5337
        if($entity->features == self::RISKS){
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;
            $this->triggerCopyShell('Risk', $copyFrom, $copyTo);
        }
        $outcomeTemplates = TableRegistry::get('outcome_templates');
        $outcomeCriterias = TableRegistry::get('outcome_criterias');
        if($entity->features == self::PERFORMANCE_OUTCOMES){
            if($entity->from_academic_period == $entity->to_academic_period){
                $this->Alert->error('CopyData.genralerror', ['reset' => true]);
                return false;
            }
            $outcomeTemplatesData = $outcomeTemplates
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->count();
            $outcomeCriteriasData = $outcomeCriterias
                ->find('all')
                ->where(['academic_period_id ' => $entity->to_academic_period])
                ->count();
            $previousOutcomeTemplatesData = $outcomeTemplates
                ->find('all')
                ->where(['academic_period_id ' => $entity->from_academic_period])
                ->count();
            $previousOutcomeCriteriasData = $outcomeCriterias
                ->find('all')
                ->where(['academic_period_id ' => $entity->from_academic_period])
                ->count();
            if($outcomeTemplatesData>=$previousOutcomeTemplatesData && $outcomeCriteriasData>=$previousOutcomeCriteriasData){
                $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                return false;
            }
        }
        // End POCOR-5337
        if ($entity->features == self::PERFORMANCE_COMPETENCIES) {
            $this->log('=======>Before triggerPerformanceCompetenciesShell', 'debug');
            $this->triggePerformanceCompetenciesShell('PerformanceCompetencies',$entity->from_academic_period, $entity->to_academic_period, $entity->competency_criterias_value, $entity->competency_templates_value, $entity->competency_items_value);
            $this->log(' <<<<<<<<<<======== After triggerPerformanceCompetenciesShell', 'debug');
        }
        //POCOR-7568 start
        if ($entity->features == self::EDUCATION_STRUCTURE) {
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;
            $this->triggerCopyShell('EducationStructureCopy', $copyFrom, $copyTo);
        }
        //POCOR-7568 end
        // Start POCOR-6423
        if ($entity->features == self::PERFORMANCE_ASSESSMENTS) {
            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $copyFrom = $from_academic_period;
            $copyTo = $to_academic_period;
            $this->triggerCopyShell('PerformanceAssessment', $copyFrom, $copyTo);
        }
        // End POCOR-6423
        // Start POCOR-7764
        if ($entity->features == self::REPORT_CARDS) {
            $copyFrom = $entity->from_academic_period;
            $copyTo = $entity->to_academic_period;
            $this->triggerCopyShell('CopyReportCard', $copyFrom, $copyTo);
        }
        // End POCOR-7764
        // Start POCOR-6425
        if ($entity->features == self::PERFORMANCE_OUTCOMES) {
            $this->log('=======>Before triggerPerformanceOutcomesShell', 'debug');
            $this->triggePerformanceOutcomesShell('PerformanceOutcomes', $entity->from_academic_period, $entity->to_academic_period);
            $this->log(' <<<<<<<<<<======== After triggerPerformanceOutcomesShell', 'debug');
        }
        // End POCOR-6425
    }


     /*
    * Function to copy Shift and Infrastucture from old academic period to new academic period
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-6825
    */

    public function triggerCopyShell($shellName, $copyFrom, $copyTo)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$copyFrom.' '.$copyTo;
        if($shellName=="EducationStructureCopy"){//POCOR-7568
            $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$copyFrom.' '.$copyTo.' '.$this->Auth->User('id');
        }
        $logs = ROOT . DS . 'logs' . DS . $shellName.'_copy.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }


    public function getFeatureOptions(){
        $options = [
            // POCOR-7924:start
            self::EDUCATION_STRUCTURE => __(self::EDUCATION_STRUCTURE),//POCOR-7568
            self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS => __(self::INSTITUTION_PROGRAMMES_GRADES_AND_SUBJECTS),
            self::SHIFTS => __(self::SHIFTS),
            self::INFRASTRUCTURE => __(self::INFRASTRUCTURE),
            self::RISKS => __(self::RISKS), // POCOR-5337
            self::PERFORMANCE_COMPETENCIES => __(self::PERFORMANCE_COMPETENCIES),
            self::PERFORMANCE_OUTCOMES => __('Performance Outcomes'),
            self::PERFORMANCE_ASSESSMENTS => __('Institution Performance Assessments'), // POCOR-6423
            self::REPORT_CARDS => __(self::REPORT_CARDS) // POCOR-7764 // POCOR-7924: end

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

    //POCOR-7576-institution programme end
    public function filter_array($array,$term,$column){
        $matches = array();
        foreach($array as $a){
            if($a[$column] == $term)
                $matches[]=$a;
        }
        return $matches;
    }

    /**
     * @param \Cake\Datasource\ConnectionInterface $connection
     * @param $tableName
     * @param $fieldName
     * @param $newId
     * @param $oldId
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function copyCustomFields(\Cake\Datasource\ConnectionInterface $connection, $tableName, $fieldName, $newId, $oldId)
    {
        $sql = "INSERT IGNORE INTO `$tableName` 
    (`id`, `text_value`, `number_value`, `decimal_value`, `textarea_value`, 
     `date_value`, `time_value`, `file`, `infrastructure_custom_field_id`, 
     `$fieldName`, `created_user_id`, `created`) 
     SELECT uuid(), `CustomFieldValues`.`text_value`, 
            `CustomFieldValues`.`number_value`, 
            `CustomFieldValues`.`decimal_value`, 
            `CustomFieldValues`.`textarea_value`, 
            `CustomFieldValues`.`date_value`, 
            `CustomFieldValues`.`time_value`, 
            `CustomFieldValues`.`file`, 
            `CustomFieldValues`.`infrastructure_custom_field_id`, 
            $newId, 
            `CustomFieldValues`.`created_user_id`, 
            NOW() FROM `$tableName` AS 
                `CustomFieldValues` WHERE `CustomFieldValues`.$fieldName = $oldId";
//        $this->log($sql, 'debug');
        $connection->query($sql);

    }
    //POCOR-7576-institution programme end 

    /*
    * Function to copy outcome_criterias and outcome_templates to new academic period
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return boolean
    * @ticket POCOR-6425
    */

    public function triggePerformanceOutcomesShell($shellName, $from_academic_period = null, $to_academic_period = null)
    {
        $args = '';
        $args .= !is_null($from_academic_period) ? ' ' . $from_academic_period : '';
        $args .= !is_null($to_academic_period) ? ' ' . $to_academic_period : '';
        $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $shellName . $args;
        $logs = ROOT . DS . 'logs' . DS . $shellName . '.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

}