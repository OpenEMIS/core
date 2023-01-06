<?php
namespace Risk\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

class RisksBehavior extends Behavior
{

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $alias = $this->_table->alias();

        $broadcaster = $this->_table;
        $listeners = [];
        $listeners[] = TableRegistry::get('Institution.InstitutionStudentRisks');

        if (!empty($listeners)) {
            $this->_table->dispatchEventToModels('Model.'. $alias .'.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $alias = $this->_table->alias();

        $broadcaster = $this->_table;
        $listeners = [];
        $listeners[] = TableRegistry::get('Institution.InstitutionStudentRisks');

        if (!empty($listeners)) {
            $this->_table->dispatchEventToModels('Model.'. $alias .'.afterDelete', [$entity], $broadcaster, $listeners);
        }
    }
}
