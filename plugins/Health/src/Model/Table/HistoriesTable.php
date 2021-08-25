<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class HistoriesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('user_health_histories');
        parent::initialize($config);

        $this->belongsTo('Conditions', ['className' => 'Health.Conditions', 'foreignKey' => 'health_condition_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Excel',[
            'excludes' => [],
            'pages' => ['index'],
        ]);
    }
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
    }

    public function onGetCurrent(Event $event, Entity $entity)
    {
        $currentOptions = $this->getSelectOptions('general.yesno');
        return $currentOptions[$entity->current];
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->setupFields($entity);
    }

    public function onUpdateFieldCurrent(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('current');
        $this->field('health_condition_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('file_content', ['after' => 'health_condition_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator->allowEmpty('file_content');
        return $validator;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){
        $session = $this->request->session();
        $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');

        $query
        ->where([
            $this->aliasField('security_user_id = ').$staffUserId
        ]);
    }
}
