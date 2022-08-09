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
use Cake\Core\Exception\Exception;
use Cake\Log\Log;
use Cake\Utility\Security;

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
 */class TransferLogsTable extends ControllerActionTable
{

    /**
     * Initialize method
     *prd_cor_arc
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('transfer_logs');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->belongsTo('AcademicPeriods', [
            'foreignKey' => 'academic_period_id',
            'joinType' => 'INNER',
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        $this->toggle('view', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator)
    {
        $validator->integer('id')->allowEmpty('id', 'create');
        $validator->dateTime('generated_on')->allowEmpty('generated_on', 'create');
        $validator->allowEmpty('generated_by', 'create');
        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules)
    {
        $rules->add($rules->existsIn(['academic_period_id'], 'AcademicPeriods'));

        return $rules;
    }

    /*public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }*/

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');    
        $this->field('generated_on');
        $this->field('generated_by');
        $this->field('features', ['sort' => false]); // POCOR-6816 
        $this->setFieldOrder(['academic_period_id','features','generated_on','generated_by']);

        //$this->Alert->info('Archive.backupReminder', ['reset' => false]);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $condition = [$this->AcademicPeriods->aliasField('current').' <> ' => "1"];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        $this->field('academic_period_id', ['type' => 'select', 'options' => $academicPeriodOptions]);
        $this->field('features', ['type' => 'select', 'options' => $this->getFeatureOptions()]); // POCOR-6816 
        $this->field('id', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('generated_by', ['visible' => false]);
        
        $this->setFieldOrder(['academic_period_id','features']); // POCOR-6816 

    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        //POCOR-6816
        $condition = [$this->AcademicPeriods->aliasField('current').' <> ' => "1"];
        $academicPeriodOptions = $this->AcademicPeriods->getYearList(['conditions' => $condition]);
        foreach($academicPeriodOptions AS $key => $val){
            $transferLogdata = $this->find('all')
                                    ->where(['academic_period_id' => $key])->toArray();
            $getFeatureOptionsCount = count($this->getFeatureOptions());
            if($getFeatureOptionsCount == count($transferLogdata)){
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $AcademicPeriods->updateAll(
                    ['editable' => 0, 'visible' => 0],    //field
                    ['id' => $key, 'current'=> 0] //condition
                );
            }
        }
        //POCOR-6816



        $this->Alert->info('Archive.backupReminder');
        try {

            $transferConnections = TableRegistry::get('TransferConnections.TransferConnections');
            $transferConnectionsData = $transferConnections->find('all')
                ->select([
                    'TransferConnections.host','TransferConnections.db_name','TransferConnections.host','TransferConnections.username','TransferConnections.password','TransferConnections.db_name'
                ])
                ->first();
            if ( base64_encode(base64_decode($transferConnectionsData['password'], true)) === $transferConnectionsData['password']){
            $db_password = $this->decrypt($transferConnectionsData['password'], Security::salt());
            }
            else {
            $db_password = $dbConnection['db_password'];
            }
            $connectiontwo = ConnectionManager::config($transferConnectionsData['db_name'], [
                'className' => 'Cake\Database\Connection',
                'driver' => 'Cake\Database\Driver\Mysql',
                'persistent' => false,
                'host' => $transferConnectionsData['host'],
                'username' => $transferConnectionsData['username'],
                'password' => $db_password,
                'database' => $transferConnectionsData['db_name'],
                'encoding' => 'utf8mb4',
                'timezone' => 'UTC',
                'cacheMetadata' => true,
            ]);
            $connection = ConnectionManager::get($transferConnectionsData['db_name']);
            $connected = $connection->connect();

        }catch (Exception $connectionError) {
            $this->Alert->warning('Connection.archiveConfigurationFail');
        }
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

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){
        $session = $this->Session;
        $superAdmin = $session->read('Auth.User.super_admin');
        $is_connection_is_online = $session->read('is_connection_stablished');
        if( ($superAdmin == 1 && $is_connection_is_online == 1) ){
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $AcademicPeriodsData = $AcademicPeriods->find()
                ->where(['current'=> 1])
                ->first();  
        
            if($entity['academic_period_id'] == $AcademicPeriodsData->id){
                $this->Alert->error('Archive.currentAcademic');
            }else{
                $entity->academic_period_id = $entity['academic_period_id'];
                $entity->generated_on = date("Y-m-d H:i:s");
                $entity->generated_by = $this->Session->read('Auth.User.id');
            }
            // return $this->Alert->warning('Connection.transferConnectionFail');
            // return true;
        }
        else{
            $this->Alert->error('Connection.archiveConfigurationFail', ['reset' => true]);
            return false;
        }
        

        // $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        
        // $AcademicPeriodsData = $AcademicPeriods->find()
        //     ->where(['current'=> 1])
        //     ->first();  
       
        // if($entity['academic_period_id'] == $AcademicPeriodsData->id){
        //     $this->Alert->error('Archive.currentAcademic');
        // }else{
        //     $entity->academic_period_id = $entity['academic_period_id'];
        //     $entity->generated_on = date("Y-m-d H:i:s");
        //     $entity->generated_by = $this->Session->read('Auth.User.id');
        // }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){
        if($entity->features == "Student Attendance"){
            // /*flag the academic period table
            // academic_periods.editable = 0, academic_periods.visible = 0 only when it is not current year-- only update columns*/
        
            $session = $this->Session;
            $superAdmin = $session->read('Auth.User.super_admin');
            $is_connection_is_online = $session->read('is_connection_stablished');
            if( ($superAdmin == 1 && $is_connection_is_online == 1) ){
                // $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                // $AcademicPeriods->updateAll(
                //     ['editable' => 0, 'visible' => 0],    //field
                //     ['id' => $entity->academic_period_id, 'current'=> 0] //condition
                // );

                $this->log('=======>Before triggerStudentAttendanceShell', 'debug');
                $this->triggerStudentAttendanceShell('StudentAttendance',$entity->academic_period_id);
                $this->log(' <<<<<<<<<<======== After triggerStudentAttendanceShell', 'debug');
            }
            else{
                $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
            }
        }else if($entity->features == "Staff Attendances"){
            /*flag the academic period table
            academic_periods.editable = 0, academic_periods.visible = 0 only when it is not current year-- only update columns*/
        
            $session = $this->Session;
            $superAdmin = $session->read('Auth.User.super_admin');
            $is_connection_is_online = $session->read('is_connection_stablished');
            if( ($superAdmin == 1 && $is_connection_is_online == 1) ){
                // $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                // $AcademicPeriods->updateAll(
                //     ['editable' => 0, 'visible' => 0],    //field
                //     ['id' => $entity->academic_period_id, 'current'=> 0] //condition
                // );

                $this->log('=======>Before triggerStaffAttendancesShell', 'debug');
                $this->triggerStaffAttendancesShell('StaffAttendances',$entity->academic_period_id);
                $this->log(' <<<<<<<<<<======== After triggerStaffAttendancesShell', 'debug');
            }
            else{
                $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
            }
        }else if($entity->features == "Student Assessments"){
            // /*flag the academic period table
            // academic_periods.editable = 0, academic_periods.visible = 0 only when it is not current year-- only update columns*/
        
            $session = $this->Session;
            $superAdmin = $session->read('Auth.User.super_admin');
            $is_connection_is_online = $session->read('is_connection_stablished');
            if( ($superAdmin == 1 && $is_connection_is_online == 1) ){
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                // $AcademicPeriods->updateAll(
                //     ['editable' => 0, 'visible' => 0],    //field
                //     ['id' => $entity->academic_period_id, 'current'=> 0] //condition
                // );

                $this->log('=======>Before triggerStudentAssessmentsShell', 'debug');
                $this->triggerStudentAssessmentsShell('StudentAssessments',$entity->academic_period_id);
                $this->log(' <<<<<<<<<<======== After triggerStudentAssessmentsShell', 'debug');
            }
            else{
                $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
            }
        }
    }


    /*
    * Function to take backup from current database and put in archive database
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-6816
    */

    public function triggerStudentAttendanceShell($shellName,$academicPeriodId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    /*
    * Function to take backup from current database and put in archive database
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-6816
    */

    public function triggerStaffAttendancesShell($shellName,$academicPeriodId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    /*
    * Function to take backup from current database and put in archive database
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return data
    * @ticket POCOR-6816
    */

    public function triggerStudentAssessmentsShell($shellName,$academicPeriodId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function triggerDatabaseTransferShell($shellName,$academicPeriodId = null)
    {
        $args = '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    /**
     * POCOR-6816 
     * add features dropdown
    */
    public function getFeatureOptions(){
        $options = [
            'Student Attendance' => __('Student Attendance'),
            'Staff Attendances' => __('Staff Attendances'),
            'Student Assessments' => __('Student Assessments'),
        ];
        return $options;
    }
    
}
