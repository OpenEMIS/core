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
class BackupLogsTable extends ControllerActionTable
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

        $this->table('backup_logs');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->toggle('view', true);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator->integer('id')->allowEmpty('id', 'create');
        $validator->allowEmpty('name', 'create');
        $validator->allowEmpty('path', 'create');
        $validator->dateTime('generated_on')->allowEmpty('generated_on', 'create');
        $validator->allowEmpty('generated_by', 'create');
        return $validator;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['visible' => false]);
        $this->field('path', ['visible' => false]);
        $this->field('generated_on');
        $this->field('generated_by');

        $this->setFieldOrder(['generated_on', 'generated_by']);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons){

        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $downloadAccess = $this->AccessControl->check(['download']);
        unset($buttons['view']);
        
        $params = [
        'id' => $entity->id
        ];

        $url = [
            'plugin' => 'Archive',
            'controller' => 'Archives',
            'action' => 'downloadSql',$entity->id,
        ];
        $buttons['downloadSql'] = [
        'label' => '<i class="fa kd-download"></i>'.__('Download'),
        'attr' => ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false],
        'url' => $url,
        ];
        
        return $buttons;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['visible' => false]);
        $this->field('path', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('generated_by', ['visible' => false]);

        $dbSize = $this->getDbSize();

        $available_disksize = $this->getDiskSpace();

        $this->field('database_size (GB)', ['attr' => ['value'=> $dbSize], 'type'=>'readonly']);
        $this->field('available_space (GB)', ['attr' => ['value'=> $available_disksize],'type'=>'readonly']);
    }

    public function getDbSize(){

        //get database size
        $connection = ConnectionManager::get('default');

        $dbConfig = $connection->config();
        $dbname = $dbConfig['database']; 
        
        $results = $connection->execute("SELECT table_schema AS 'Database',  ROUND(SUM(data_length + index_length) / 1024 / 1024 / 1024, 2) AS 'Size' FROM information_schema.TABLES WHERE table_schema = '$dbname' ORDER BY (data_length + index_length) DESC")->fetch('assoc');
        
        $dbsize = $results['Size'];
        
        return $dbsize;

    }

    public function getDiskSpace(){

        //get available disk size
        $available_disksize = round(disk_free_space('/') / 1024 / 1024 / 1024, 2);

        return $available_disksize;
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

        $dbSize = $this->getDbSize();
        $available_disksize = $this->getDiskSpace();

        $fileName = 'Backup_SQL_' . time();

        $entity->name = $fileName;
        $entity->path = "webroot/export/backup";
        $entity->generated_on = date("Y-m-d H:i:s");
        $entity->generated_by = $this->Session->read('Auth.User.id');
        
        if($dbsize >= $available_disksize){
            $event->stopPropagation();
            $this->Alert->error('Archive.lessSpace', ['reset' => true]);
        }else{

            if (!file_exists(WWW_ROOT .'export/backup')) {
                mkdir(WWW_ROOT .'export/backup', 0777, true);
            }
       
            $this->log('=======>Before triggerDatabaseSqlDumpShell', 'debug');
            $this->triggerDatabaseSqlDumpShell('DatabaseSqlDump',$fileName);
            $this->log(' <<<<<<<<<<======== After triggerDatabaseSqlDumpShell', 'debug');
        }
        
    }

    public function triggerDatabaseSqlDumpShell($shellName,$fileName = null)
    {

        $args = '';
        $args .= !is_null($fileName) ? ' '.$fileName : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
    
}
