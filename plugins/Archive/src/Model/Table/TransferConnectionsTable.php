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
use Cake\Core\Exception\Exception;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\Configure;
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
 */
class TransferConnectionsTable extends ControllerActionTable
{
    use MessagesTrait;

    private $databaseType = [
        1 => ['id' => 1, 'name' => 'MySql'],
        2 => ['id' => 2, 'name' => 'Postgres'],
        3 => ['id' => 3, 'name' => 'SqlServer'],
        4 => ['id' => 4, 'name' => 'Sqlite']
    ];
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->table('data_management_connections');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->toggle('remove', false);
        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('index', false);

    }

    public function validationDefault(Validator $validator)
    {
        $validator->integer('id')->allowEmpty('id', 'create');
        $validator->requirePresence('name', 'create')->notEmpty('name');
        $validator->requirePresence('db_type_id', 'create')->notEmpty('db_type_id');
        $validator->requirePresence('host', 'create')->notEmpty('host');
        $validator->requirePresence('host_port', 'create')->notEmpty('host_port');
        $validator->requirePresence('db_name', 'create')->notEmpty('db_name');
        $validator->requirePresence('username', 'create')->notEmpty('username');
        $validator->allowEmpty('password', 'create');
        
        $validator->integer('conn_status_id')->allowEmpty('conn_status_id', 'create');
        $validator->dateTime('status_checked')->allowEmpty('status_checked', 'create');
        //$validator->allowEmpty('modified_user_id');
        $validator->dateTime('modified')->allowEmpty('modified', 'create');
        //$validator->integer('created_user_id')->allowEmpty('created_user_id', 'create');
        $validator->dateTime('created')->allowEmpty('created', 'create');

        return $validator;
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Remove back toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        unset($toolbarButtonsArray['back']);
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);

    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.onGetFormButtons'] = 'onGetFormButtons';
        return $events;
    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {
        if ($this->action == 'edit') {
            $originalButtons = $buttons->getArrayCopy();
            $startTestButton = [
                [
                    'name' => '<i class="fa fa-chain-broken"></i>' . __('Test'),
                    'attr' => [
                        'class' => 'btn btn-default',
                        'name' => 'submit',
                        'value' => 'testConnection',
                        'div' => false
                    ]
                ]
            ];

            array_splice($originalButtons, 0, 0, $startTestButton);
            $buttons->exchangeArray($originalButtons);
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'db_type_id':
                return __('Database Type');
            case 'db_name':
                return __('Database Name');
            case 'conn_status_id':
                return __('Status');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function EditOnTestConnection(){
        $post_data= $this->request->data;
        if(isset($post_data)){
            $connection = ConnectionManager::config($post_data['TransferConnections']['name'], [
                'className' => 'Cake\Database\Connection',
                'driver' => 'Cake\Database\Driver\Mysql',
                'persistent' => false,
                'host' => $post_data['TransferConnections']['host'],
                'username' => $post_data['TransferConnections']['username'],
                'password' => $post_data['TransferConnections']['password'],
                'database' => $post_data['TransferConnections']['db_name'],
                'encoding' => 'utf8mb4',
                'timezone' => 'UTC',
                'cacheMetadata' => true,
            ]);
    
            try {
                $connection = ConnectionManager::get($post_data['TransferConnections']['name']);
                $connected = $connection->connect();
                $this->Alert->success('Connection.testConnectionSuccess', ['reset' => true]);
                // $this->Session->write('is_connection_stablished', "1");
    
            }catch (Exception $connectionError) {
                // $this->Session->write('is_connection_stablished', "0");
                $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
            }
        }

    }   
    
    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name');    
        $this->field('db_type_id');
        $this->field('host');
        $this->field('host_port');    
        $this->field('db_name');
        $this->field('username');
        $this->field('password', ['visible' => false]);    
        $this->field('conn_status_id');
        $this->field('status_checked');
        $this->field('modified_user_id');    
        $this->field('modified');
        $this->field('created_user_id');
        $this->field('created');

        $this->setFieldOrder(['name','db_type_id','host','host_port','db_name','username','conn_status_id','status_checked','modified_user_id','modified','created_user_id','created']);
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name');    
        $this->field('db_type_id');
        $this->field('host');
        $this->field('host_port');    
        $this->field('db_name');
        $this->field('username');
        $this->field('password', ['visible' => true, 'type' => 'password']);    
        $this->field('conn_status_id', ['visible' => false]);
        $this->field('status_checked', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);    
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        $this->setFieldOrder(['name','db_type_id','host','host_port','db_name','username','password']);

    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        //Setup fields
        list($databaseTypeOptions) = array_values($this->getSelectOptions());

        $this->fields['db_type_id']['type'] = 'select';
        $this->fields['db_type_id']['options'] = $databaseTypeOptions;
    }

    public function getSelectOptions()
    {
        //Return all required options and their key
        $databaseTypeOptions = [];
        foreach ($this->databaseType as $key => $databaseType) {
            $databaseTypeOptions[$databaseType['id']] = __($databaseType['name']);
        }
        $selectedDatabaseType = key($databaseTypeOptions);

        return compact('databaseTypeOptions', 'selectedDatabaseType');
    }
    
    public function onGetDbTypeId(Event $event, Entity $entity)
    {
        list($databaseTypeOptions) = array_values($this->getSelectOptions());

        return $databaseTypeOptions[$entity->db_type_id];
    }

    public function onGetConnStatusId(Event $event, Entity $entity)
    {
        if($entity->conn_status_id == "1"){
            return $entity->conn_status_id = '<b style="color:green;">Online</b>';
        }else{
            return $entity->conn_status_id = '<b style="color:red;">Offline</b>';
        }
    }

    public function onGetModifiedUserId(Event $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->modified_user_id])
            ->first();

        return $entity->modified_user_id = $result->first_name.' '.$result->last_name;
    }

    public function onGetCreatedUserId(Event $event, Entity $entity)
    {
        $Users = TableRegistry::get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->created_user_id])
            ->first();

        return $entity->created_user_id = $result->first_name.' '.$result->last_name;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){

        $post_data= $this->request->data;
        if(isset($post_data)){
            $connection = ConnectionManager::config($post_data['TransferConnections']['name'], [
                'className' => 'Cake\Database\Connection',
                'driver' => 'Cake\Database\Driver\Mysql',
                'persistent' => false,
                'host' => $post_data['TransferConnections']['host'],
                'username' => $post_data['TransferConnections']['username'],
                'password' => $post_data['TransferConnections']['password'],
                'database' => $post_data['TransferConnections']['db_name'],
                'encoding' => 'utf8mb4',
                'timezone' => 'UTC',
                'cacheMetadata' => true,
            ]);
    
            try {
                $connection = ConnectionManager::get($post_data['TransferConnections']['name']);
                $connected = $connection->connect();
                $this->Alert->success('Connection.testConnectionSuccess', ['reset' => true]);
                $this->Session->write('is_connection_stablished', "1");
                //START: POCOR-6770
                $this->updateAll(
                    ['conn_status_id' => 1],    //field
                    [
                     'host' => $post_data['TransferConnections']['host'], 
                     'db_name'=> $post_data['TransferConnections']['db_name'],
                     'username' => $post_data['TransferConnections']['username']
                     ] //condition
                );
                //END: POCOR-6770
    
            }catch (Exception $connectionError) {
                $this->Session->write('is_connection_stablished', "0");
                $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
                //START: POCOR-6770
                $this->updateAll(
                    ['conn_status_id' => 0],    //field
                    [
                     'host' => $post_data['TransferConnections']['host'], 
                     'db_name'=> $post_data['TransferConnections']['db_name'],
                     'username' => $post_data['TransferConnections']['username']
                     ] //condition
                );
                //END: POCOR-6770
                
            }
        }
        $is_connection_stablished = $this->Session->read('is_connection_stablished');
        if($is_connection_stablished == "0"){
            $entity->conn_status_id = "0";
        }
        else{
            $entity->conn_status_id = "1";
        }
        //POCOR-6799[START]
        $collection = $connection->schemaCollection();
        $tableSchema = $collection->listTables();
        if (!in_array('institution_staff_attendances', $tableSchema)) {

            $connection->execute("CREATE TABLE IF NOT EXISTS `institution_staff_attendances` (
                `id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
                `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to instututions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `date` date NOT NULL,
                `time_in` time DEFAULT NULL,
                `time_out` time DEFAULT NULL,
                `comment` text COLLATE utf8mb4_unicode_ci,
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL,
                `absence_type_id` int(11) DEFAULT '1'
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the attendance records for staff';
              ");


            $connection->execute("CREATE TABLE IF NOT EXISTS `institution_staff_leave` (
                `id` int(11) NOT NULL,
                `date_from` date NOT NULL,
                `date_to` date NOT NULL,
                `start_time` time DEFAULT NULL,
                `end_time` time DEFAULT NULL,
                `full_day` int(1) NOT NULL DEFAULT '1',
                `comments` text COLLATE utf8mb4_unicode_ci,
                `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `staff_leave_type_id` int(11) NOT NULL COMMENT 'links to staff_leave_types.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `assignee_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to security_users.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `status_id` int(11) NOT NULL COMMENT 'links to workflow_steps.id',
                `number_of_days` decimal(5,1) NOT NULL,
                `file_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `file_content` longblob,
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of leave for a specific staff';
            ");

            $connection->execute("CREATE TABLE IF NOT EXISTS `assessment_item_results` (
                `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
                `marks` decimal(6,2) DEFAULT NULL,
                `assessment_grading_option_id` int(11) DEFAULT NULL,
                `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
                `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
                `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the assessment results for an individual student in an institution'
            PARTITION BY HASH (`assessment_id`)
            PARTITIONS 101");

            $connection->execute("CREATE TABLE IF NOT EXISTS `institution_student_absences` (
                `id` int(11) NOT NULL,
                `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
                `education_grade_id` int(11) NOT NULL DEFAULT '0',
                `date` date NOT NULL,
                `absence_type_id` int(11) NOT NULL COMMENT 'links to student_absence_reasons.id',
                `institution_student_absence_day_id` int(11) DEFAULT NULL COMMENT 'links to institution_student_absence_days.id',
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains absence records of students for day type attendance marking';
            ");

            $connection->execute("CREATE TABLE IF NOT EXISTS `student_attendance_marked_records` (
                `institution_id` int(11) NOT NULL COMMENT 'links to instututions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
                `education_grade_id` int(11) NOT NULL DEFAULT '0',
                `date` date NOT NULL,
                `period` int(1) NOT NULL,
                `subject_id` int(11) NOT NULL DEFAULT '0',
                `no_scheduled_class` tinyint(4) NOT NULL DEFAULT '0'
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains attendance marking records';
            ");

            $connection->execute("CREATE TABLE IF NOT EXISTS `student_attendance_mark_types` (
                `id` int(11) NOT NULL,
                `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `code` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                `education_grade_id` int(11) DEFAULT NULL COMMENT 'links to education_grades.id',
                `academic_period_id` int(11) DEFAULT NULL COMMENT 'links to academic_periods.id',
                `student_attendance_type_id` int(11) NOT NULL COMMENT 'links to student_attendance_types.id',
                `attendance_per_day` int(1) NOT NULL,
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains different attendance marking for different academic periods for different programme'");

            $connection->execute("CREATE TABLE IF NOT EXISTS `institution_student_absence_details` (
                `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
                `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
                `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
                `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
                `education_grade_id` int(11) NOT NULL DEFAULT '0',
                `date` date NOT NULL,
                `period` int(1) NOT NULL,
                `comment` text,
                `absence_type_id` int(11) NOT NULL COMMENT 'links to student_absence_reasons.id',
                `student_absence_reason_id` int(11) DEFAULT NULL COMMENT 'links to absence_types.id',
                `subject_id` int(11) NOT NULL DEFAULT '0',
                `modified_user_id` int(11) DEFAULT NULL,
                `modified` datetime DEFAULT NULL,
                `created_user_id` int(11) NOT NULL,
                `created` datetime NOT NULL
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains absence records of students for day type attendance marking'");

        }
        //POCOR-6799[END]
        // $password  = ((new DefaultPasswordHasher)->hash($entity->password));
        // $password = $this->PasswordHash->encrypt($entity->password, Security::salt());
        $password = $this->encrypt($entity->password, Security::salt());
        
        $entity->password = $password;
        $entity->modified_user_id = $this->Session->read('Auth.User.id');
        $entity->created_user_id = $this->Session->read('Auth.User.id');
        
    }

    public  function encrypt($pure_string, $secretHash) {

        $iv = substr($secretHash, 0, 16);
        $encryptedMessage = openssl_encrypt($pure_string, "AES-256-CBC", $secretHash, $raw_input = false, $iv);
        $encrypted = base64_encode(
            $encryptedMessage
        );
        return $encrypted;
    }
}
