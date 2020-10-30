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
 */class DeletedLogsTable extends ControllerActionTable
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

        $this->table('deleted_logs');
        $this->displayField('id');
        $this->primaryKey('id');

        $this->belongsTo('AcademicPeriods', [
            'foreignKey' => 'academic_period_id',
            'joinType' => 'INNER',
            'className' => 'AcademicPeriod.AcademicPeriods'
        ]);

        /*$this->belongsTo('Users', [
            'className' => 'User.Users', 
            'foreignKey' => 'generated_by'
        ]);*/

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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');    
        $this->field('generated_on');
        $this->field('generated_by');

        $this->setFieldOrder(['academic_period_id','generated_on','generated_by']);
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select']);
        
        $this->field('id', ['visible' => false]);
        $this->field('generated_on', ['visible' => false]);
        $this->field('generated_by', ['visible' => false]);
        
        $this->setFieldOrder(['academic_period_id']);

        $this->Alert->warning('Archive.backupReminder', ['reset' => true]);
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

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        
        $AcademicPeriodsData = $AcademicPeriods->find()
            ->where(['current'=> 1])
            ->first();  
       
        if($entity['academic_period_id'] == $AcademicPeriodsData->id){
            $this->Alert->error('Archive.currentAcademic', ['reset' => true]);
        }else{
            $entity->academic_period_id = $entity['academic_period_id'];
            $entity->generated_on = date("Y-m-d H:i:s");
            $entity->generated_by = $this->Session->read('Auth.User.id');
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $data){

        $this->log('=======>Before triggerDatabaseTransferShell', 'debug');
        $this->triggerDatabaseTransferShell('DatabaseTransfer',$entity->academic_period_id);
        $this->log(' <<<<<<<<<<======== After triggerDatabaseTransferShell', 'debug');

        $url = $this->url('index');
        return $this->controller->redirect($url);
        
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
    
}
