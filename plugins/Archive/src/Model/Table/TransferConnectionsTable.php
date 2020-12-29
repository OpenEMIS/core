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

        $this->table('transfer_connections');
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
    
            }catch (Exception $connectionError) {
                $this->Session->write('is_connection_stablished', "0");
                $this->Alert->error('Connection.testConnectionFail', ['reset' => true]);
            }
        }
        $is_connection_stablished = $this->Session->read('is_connection_stablished');
        if($is_connection_stablished == "0"){
            $entity->conn_status_id = "0";
        }
        else{
            $entity->conn_status_id = "1";
        }
        // echo "<pre>";print_r($post_data);exit;
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
