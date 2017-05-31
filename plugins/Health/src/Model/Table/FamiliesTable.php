<?php
namespace Health\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class FamiliesTable extends ControllerActionTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('user_health_families');
        parent::initialize($config);

        $this->belongsTo('Relationships', ['className' => 'Health.Relationships', 'foreignKey' => 'health_relationship_id']);
        $this->belongsTo('Conditions', ['className' => 'Health.Conditions', 'foreignKey' => 'health_condition_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);

        $this->addBehavior('Health.Health');
    }

    public function onGetCurrent(Event $event, Entity $entity)
    {
        $currentOptions = $this->getSelectOptions('general.yesno');
        return $currentOptions[$entity->current];
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
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
        $this->field('health_relationship_id', ['type' => 'select', 'after' => 'comment']);
        $this->field('health_condition_id', ['type' => 'select', 'after' => 'health_relationship_id']);
    }
}
