<?php
namespace Guardian\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Directory\Model\Table\DirectoriesTable as UserTable;

class StudentUserTable extends UserTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Guardian.afterSave'] = 'guardianAfterSave';
        return $events;
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);

        unset($extra['toolbarButtons']['back']);

        if ($extra['toolbarButtons']->offsetExists('export')) {
            unset($extra['toolbarButtons']['export']);
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // MUST set user_type to request query before call parent's beforeAction
        $this->request->query['user_type'] = UserTable::STUDENT;
        parent::beforeAction($event, $extra);
        //parent::hideOtherInformationSection($this->controller->name, $this->action);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);

        $this->fields['nationality_id']['type'] = 'readonly';
        if (!empty($entity->main_nationality)) {
            $this->fields['nationality_id']['attr']['value'] = $entity->main_nationality->name;
        }

        $this->fields['identity_type_id']['type'] = 'readonly';
        if (!empty($entity->main_identity_type)) {
            $this->fields['identity_type_id']['attr']['value'] = $entity->main_identity_type->name;
        }

        $this->fields['identity_number']['type'] = 'readonly'; //cant edit identity_number field value as its value is auto updated.
    }

    // for info tooltip
    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';
        return $tooltipMessage;
    }

    private function setupTabElements($entity)
    {
        $studentId = $this->Session->read('Student.Students.id');
        $tabElements = $this->controller->getUserTabElements(['id' => $studentId, 'userRole' => 'Students']);

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());        
    }
}
