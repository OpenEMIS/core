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
class CopyAcademicPeriodsTable extends ControllerActionTable
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

        $this->table('copy_academic_periods');
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

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $condition = [];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        $this->field('from_academic_period', ['type' => 'select', 'options' => $academicPeriodOptions]);
        $this->field('to_academic_period', ['type' => 'select', 'options' => $academicPeriodOptions]);
        $this->field('features', ['type' => 'select', 'options' => $this->getFeatureOptions()]);
        $this->setFieldOrder(['from_academic_period','to_academic_period','features']);

    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){
        if($entity->from_academic_period == $entity->to_academic_period){
            $this->Alert->error('CopyData.genralerror', ['reset' => true]);
            return false;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){
        $connection = ConnectionManager::get('default');
        $EducationSystems = TableRegistry::get('Education.EducationSystems');
        $EducationLevels = TableRegistry::get('Education.EducationLevels');
        $EducationCycles = TableRegistry::get('Education.EducationCycles');
        $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $AcademicPeriods = TableRegistry::get('Academic.AcademicPeriods');
        
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
                    $startDate = $AcademicPeriodsData['start_date']->format('Y-m-d');

                    $InstitutionGrades->updateAll(
                        ['start_date' => $AcademicPeriodsData['start_date'], 'start_year' => $AcademicPeriodsData['start_year']],    //field
                        ['education_grade_id' => $rowData['education_grade_id'], 'institution_id'=> $rowData['institution_id']] //condition
                    );
                }
        }

        // $this->log('=======>Before triggerCopyDataShell', 'debug');
        // $this->triggerCopyDataShell('CopyData',$entity->to_academic_period);
        // $this->log(' <<<<<<<<<<======== After triggerCopyDataShell', 'debug');
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
            'Institution programmes and Grade' => __('Institution programmes and Grade')
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

    // public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons){

    //     $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

    //     $downloadAccess = $this->AccessControl->check(['download']);
    //     unset($buttons['view']);
        
    //     $params = [
    //     'id' => $entity->id
    //     ];

    //     $url = [
    //         'plugin' => 'Archive',
    //         'controller' => 'Archives',
    //         'action' => 'downloadSql',$entity->id,
    //     ];
    //     $buttons['downloadSql'] = [
    //     'label' => '<i class="fa kd-download"></i>'.__('Download'),
    //     'attr' => ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false],
    //     'url' => $url,
    //     ];
        
    //     return $buttons;
    // }

    // public function addBeforeAction(Event $event, ArrayObject $extra)
    // {
    //     $this->field('name', ['visible' => false]);
    //     $this->field('path', ['visible' => false]);
    //     $this->field('generated_on', ['visible' => false]);
    //     $this->field('generated_by', ['visible' => false]);

    //     $dbSize = $this->getDbSize();

    //     $available_disksize = $this->getDiskSpace();

    //     $this->field('database_size (GB)', ['attr' => ['value'=> $dbSize], 'type'=>'readonly']);
    //     $this->field('available_space (GB)', ['attr' => ['value'=> $available_disksize],'type'=>'readonly']);
    // }

    // public function getDbSize(){

    //     //get database size
    //     $connection = ConnectionManager::get('default');

    //     $dbConfig = $connection->config();
    //     $dbname = $dbConfig['database']; 
        
    //     $results = $connection->execute("SELECT table_schema AS 'Database',  ROUND(SUM(data_length + index_length) / 1024 / 1024 / 1024, 2) AS 'Size' FROM information_schema.TABLES WHERE table_schema = '$dbname' ORDER BY (data_length + index_length) DESC")->fetch('assoc');
        
    //     $dbsize = $results['Size'];
        
    //     return $dbsize;

    // }

    // public function getDiskSpace(){

    //     //get available disk size
    //     $available_disksize = round(disk_free_space('/') / 1024 / 1024 / 1024, 2);

    //     return $available_disksize;
    // }

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

    // public function beforeSave(Event $event, Entity $entity, ArrayObject $data){

    //     $dbSize = $this->getDbSize();
    //     $available_disksize = $this->getDiskSpace();

    //     $fileName = 'Backup_SQL_' . time();

    //     $entity->name = $fileName;
    //     $entity->path = "webroot/export/backup";
    //     $entity->generated_on = date("Y-m-d H:i:s");
    //     $entity->generated_by = $this->Session->read('Auth.User.id');
        
    //     if($dbsize >= $available_disksize){
    //         $event->stopPropagation();
    //         $this->Alert->error('Archive.lessSpace', ['reset' => true]);
    //     }else{

    //         if (!file_exists(WWW_ROOT .'export/backup')) {
    //             mkdir(WWW_ROOT .'export/backup', 0777, true);
    //         }
       
    //         $this->log('=======>Before triggerDatabaseSqlDumpShell', 'debug');
    //         $this->triggerDatabaseSqlDumpShell('DatabaseSqlDump',$fileName);
    //         $this->log(' <<<<<<<<<<======== After triggerDatabaseSqlDumpShell', 'debug');
    //     }
        
    // }

    // public function triggerDatabaseSqlDumpShell($shellName,$fileName = null)
    // {

    //     $args = '';
    //     $args .= !is_null($fileName) ? ' '.$fileName : '';

    //     $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
    //     $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
    //     $shellCmd = $cmd . ' >> ' . $logs;
    //     exec($shellCmd);
    //     Log::write('debug', $shellCmd);
    // }
    
}
