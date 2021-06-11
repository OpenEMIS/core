<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class TestsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_health_tests');
        parent::initialize($config);

        $this->belongsTo('TestTypes', ['className' => 'Health.TestTypes', 'foreignKey' => 'health_test_type_id']);
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
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('health_test_type_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'health_test_type_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'health_test_type_id','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }
}
