<?php
namespace Risk\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Validation\Validator;

class RisksBehavior extends Behavior
{

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $alias = $this->_table->getAlias();

        $broadcaster = $this->_table;
        $listeners = [];
        $listeners[] = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentRisks');

        if (!empty($listeners)) {
            $this->_table->dispatchEventToModels('Model.'. $alias .'.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $alias = $this->_table->getAlias();

        $broadcaster = $this->_table;
        $listeners = [];
        $listeners[] = TableRegistry::getTableLocator()->get('Institution.InstitutionStudentRisks');

        if (!empty($listeners)) {
            $this->_table->dispatchEventToModels('Model.'. $alias .'.afterDelete', [$entity], $broadcaster, $listeners);
        }
    }
}
