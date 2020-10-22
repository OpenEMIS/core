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

/**
 * Archives Model
 *
 * @method \App\Model\Entity\Archive get($primaryKey, $options = [])
 * @method \App\Model\Entity\Archive newEntity($data = null, array $options = [])
 * @method \App\Model\Entity\Archive[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Archive|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Archive patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Archive[] patchEntities($entities, array $data, array $options = [])
 * @method \App\Model\Entity\Archive findOrCreate($search, callable $callback = null, $options = [])
 */class ArchivesTable extends ControllerActionTable
{

    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config)
    {
        parent::initialize($config);
        //echo '456'; die;

        $this->table('archives');
        $this->displayField('name');
        $this->primaryKey('id');

        $this->belongsTo('Users', [
            'className' => 'User.Users', 
            'foreignKey' => 'generated_by'
        ]);

        $this->toggle('view', true);
        $this->toggle('edit', false);
        $this->toggle('remove', false);

        // $this->behaviors()->get('ControllerAction')->config(
        //     'actions.downloadExportDB.show',
        //     true
        // );

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
        $validator->allowEmpty('name', 'create');
        $validator->allowEmpty('path', 'create');
        $validator->dateTime('generated_on')->allowEmpty('generated_on', 'create');
        $validator->allowEmpty('generated_by', 'create');
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.download'] = 'download';
		$events['ControllerAction.Model.downloadPdf'] = 'downloadExportDB';
        return $events;
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

        //$url = $this->url('downloadSql');
        $url = [
            'plugin' => 'Archive',
            'controller' => 'Archives',
            'action' => 'downloadSql',$entity->id,
        ];
        //$url[1] = $this->paramsEncode($params);
        $buttons['downloadSql'] = [
        'label' => '<i class="fa kd-download"></i>'.__('Download'),
        'attr' => ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false],
        'url' => $url,
        ];
        
        return $buttons;
    }

    
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        //echo '<pre>'; print_r($event); die;
        $this->field('name', ['visible' => false]);
        $this->field('path', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('generated_by', ['visible' => false]);

        $dbSize = $this->getDbSize();

        $available_disksize = $this->getDiskSpace();

        $this->field('size', ['attr' => ['value'=> $dbSize], 'type'=>'readonly']);
        $this->field('available_disk', ['attr' => ['value'=> $available_disksize],'type'=>'readonly']);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $data){

        $dbSize = $this->getDbSize();
        $available_disksize = $this->getDiskSpace();

        if($dbsize <= $available_disksize){
            $this->Alert->error('Please make sure there is enough space for backup',['reset' => true]);
        }

        $fileName = 'Backup_SQL_' . time();
        //exec('C:/xampp/mysql/bin/mysqldump --user=root --password= --host=localhost openemis_core > C:/xampp/htdocs/pocor-openemis-core/webroot/export/'.$fileName.'.sql');

        $entity->name = $fileName;
        $entity->path = "webroot/export/backup";
        $entity->generated_on = date("Y-m-d H:i:s");
        $entity->generated_by = $this->Session->read('Auth.User.id');

    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){
        //echo 'came'; die;
    }

    public function getDbSize(){

        //get database size
        $connection = ConnectionManager::get('default');
        $results = $connection->execute('SELECT table_schema AS "Database",  ROUND(SUM(data_length + index_length) / 1024 / 1024 / 1024, 2) AS "Size" FROM information_schema.TABLES WHERE table_schema = "openemis_core" ORDER BY (data_length + index_length) DESC')->fetch('assoc');
        
        $dbsize = $results['Size'];
        
        return $dbsize;

    }

    public function getDiskSpace(){

        //get available disk size
        $available_disksize = round(disk_free_space('/') / 1024 / 1024 / 1024, 2);

        return $available_disksize;
    }
}
