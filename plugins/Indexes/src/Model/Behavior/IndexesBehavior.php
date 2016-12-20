<?php
namespace Indexes\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;

class IndexesBehavior extends Behavior
{
    public function initialize(array $config)
    {

    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $alias = $this->_table->alias();
pr('afterSave - IndexesBehavior');
pr($alias);
// pr($options);
// pr($entity);
die;
        $broadcaster = $this->_table;
        $listeners = [];
        $listeners[] = TableRegistry::get('Institution.InstitutionStudentIndexes');

        if (!empty($listeners)) {
            $this->_table->dispatchEventToModels('Model.'. $alias .'.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
pr('afterDelete - IndexesBehavior');
pr($alias);
die;
        $alias = $this->_table->alias();

        $broadcaster = $this->_table;
        $listeners = [];
        $listeners[] = TableRegistry::get('Institution.InstitutionStudentIndexes');

        if (!empty($listeners)) {
            $this->_table->dispatchEventToModels('Model.'. $alias .'.afterDelete', [$entity], $broadcaster, $listeners);
        }
    }
}
