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
        if($entity->features == "Institution programmes and Grade"){
            if($entity->from_academic_period == $entity->to_academic_period){
                $this->Alert->error('CopyData.genralerror', ['reset' => true]);
                return false;
            }

            $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationSystems = TableRegistry::get('Education.EducationSystems');
            if($entity->to_academic_period){
                
                $ToAcademicPeriodsData = $AcademicPeriods
                ->find()
                ->select(['start_date', 'start_year','end_date'])
                ->where(['id' => $entity->to_academic_period])
                ->first();

                $InstitutionGradesdata = $InstitutionGrades
                    ->find('all')
                    ->where(['start_date' => $ToAcademicPeriodsData['start_date']])
                    ->toArray();
                if(!empty($InstitutionGradesdata)){
                    $this->Alert->error('CopyData.alreadyexist', ['reset' => true]);
                    return false;
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
            if($entity->from_academic_period){
                
                $ToAcademicPeriodsData = $AcademicPeriods
                ->find()
                ->select(['start_date', 'start_year','end_date'])
                ->where(['id' => $entity->from_academic_period])
                ->first();

                $InstitutionGradesdata = $InstitutionGrades
                    ->find('all')
                    ->where(['start_date' => $ToAcademicPeriodsData['start_date']])
                    ->toArray();
                if(empty($InstitutionGradesdata)){
                    $this->Alert->error('CopyData.nodataexist', ['reset' => true]);
                    return false;
                }
            }
        }
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){
        if($entity->features == "Institution programmes and Grade"){
            //This code is for update the corret academic period in institution_grade table [Start]
            ini_set('memory_limit', '2G'); //POCOR-6893
            $connection = ConnectionManager::get('default');
            $EducationSystems = TableRegistry::get('Education.EducationSystems');
            $EducationLevels = TableRegistry::get('Education.EducationLevels');
            $EducationCycles = TableRegistry::get('Education.EducationCycles');
            $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $Institutions = TableRegistry::get('Institution.Institutions');
            $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');

            $InstitutionGradesdata = $InstitutionGrades
                    ->find('all')
                    ->toArray();
            // if(!empty($InstitutionGradesdata)){
            //     foreach($InstitutionGradesdata AS $InstitutionGradesValue){
            //         $EducationGradesData = $EducationGrades
            //                             ->find()
            //                             ->where([$EducationGrades->aliasField('id') =>$InstitutionGradesValue['education_grade_id']])
            //                             ->All()
            //                             ->toArray();
                    
            //         $EducationProgrammesData = $EducationProgrammes
            //                             ->find()
            //                             ->where([$EducationProgrammes->aliasField('id') =>$EducationGradesData[0]['education_programme_id']])
            //                             ->All()
            //                             ->toArray();
        
            //         $EducationCyclesData = $EducationCycles
            //                             ->find()
            //                             ->where([$EducationCycles->aliasField('id') =>$EducationProgrammesData[0]['education_cycle_id']])
            //                             ->All()
            //                             ->toArray();
                    
            //         $EducationLevelsData = $EducationLevels
            //         ->find()
            //         ->where(['id' => $EducationCyclesData[0]['education_level_id']])
            //         ->toArray();
        
            //         $EducationSystemsData = $EducationSystems
            //         ->find()
            //         ->where(['id' => $EducationLevelsData[0]['education_system_id']])
            //         ->first();
        
            //         $AcademicPeriodsData = $AcademicPeriods
            //                 ->find()
            //                 ->select(['start_date', 'start_year'])
            //                 ->where(['id' => $EducationSystemsData['academic_period_id']])
            //                 ->first();

            //         if(!empty($AcademicPeriodsData)){
            //             $InstitutionGrades->updateAll(
            //                 ['start_date' => $AcademicPeriodsData['start_date'], 'start_year' => $AcademicPeriodsData['start_year']],    //field
            //                 ['education_grade_id' => $InstitutionGradesValue['education_grade_id'], 'institution_id'=> $InstitutionGradesValue['institution_id']] //condition
            //             );
            //         }
            //     }
            // }

            //This code is for update the corret academic period in institution_grade table [END]

            //This code is for copy one academic period to onother[Start]

            $from_academic_period = $entity->from_academic_period;
            $to_academic_period = $entity->to_academic_period;
            $FromAcademicPeriodsData = $AcademicPeriods
                            ->find()
                            ->select(['start_date', 'start_year'])
                            ->where(['id' => $from_academic_period])
                            ->first();

            $ToAcademicPeriodsData = $AcademicPeriods
            ->find()
            ->select(['start_date', 'start_year','end_date'])
            ->where(['id' => $to_academic_period])
            ->first();

            $InstitutionGradesdataToInsert = $InstitutionGrades
            ->find('all')
            ->where(['start_year' => $FromAcademicPeriodsData['start_year']])
            ->toArray();
            
            foreach($InstitutionGradesdataToInsert AS $InstitutionGradesdataValue){
                
                try{
                    $statement = $connection->prepare('INSERT INTO institution_grades 
                    (
                    education_grade_id, 
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
                    'education_grade_id' => $InstitutionGradesdataValue->education_grade_id,
                    'start_date' => $ToAcademicPeriodsData['start_date']->format('Y-m-d'),
                    'start_year' => $ToAcademicPeriodsData['start_year'],
                    'end_date' => null,
                    'end_year' => null,
                    'institution_id' => $InstitutionGradesdataValue->institution_id,
                    'modified_user_id' => 2,
                    'modified' => date('Y-m-d H:i:s'),
                    'created_user_id' => 2,
                    'created' => date('Y-m-d H:i:s')
                    ]);
                
                }catch (PDOException $e) {
                    echo "<pre>";print_r($e);die;
                }
            }

            //This code is for copy one academic period to onother[END]


            //This code is for update correct education grade[Start]
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
            // echo "<pre>";print_r($row);die;
            foreach($row AS $rowData){
                $InstitutionGrades->updateAll(
                    ['education_grade_id' => $rowData['correct_grade_id']],    //field
                    ['education_grade_id' => $rowData['education_grade_id'], 'institution_id'=>$rowData['institution_id'],  'start_date' => $final_from_start_date, 'start_year' => $to_start_year]
                );
            }
            //This code is for update correct education grade[End]


            //This code is for update the corret academic period in institution_grade table [END]
        }
        if($entity->features == "Performance Competencies"){
            $this->log('=======>Before triggerPerformanceCompetenciesShell', 'debug');
            $this->triggePerformanceCompetenciesShell('PerformanceCompetencies',$entity->from_academic_period, $entity->to_academic_period, $entity->competency_criterias_value, $entity->competency_templates_value, $entity->competency_items_value);
            $this->log(' <<<<<<<<<<======== After triggerPerformanceCompetenciesShell', 'debug');
        }
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


    public function triggerCopyDataShell($shellName,$academicPeriodId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }


    public function getFeatureOptions(){
        $options = [
            'Institution programmes and Grade' => __('Institution programmes and Grade'),
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
    
}
