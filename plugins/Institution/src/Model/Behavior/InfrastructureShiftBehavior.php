<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class InfrastructureShiftBehavior extends Behavior {
    private $isOwner = false;
    private $isOccupier = false;

	public function initialize(array $config) {
		parent::initialize($config);
	}

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 100];
        $events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 100];
        $events['Model.isRecordExists'] = 'isRecordExists';
        $events['ControllerAction.Model.beforeAction'] = 'beforeAction';
        $events['ControllerAction.Model.index.afterAction'] = 'indexAfterAction';
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        return $events;
    }

    public function isRecordExists(Event $event)
    {
        $callable = function($model, $params) {
            return true;
        };
        return $callable;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
        // Occupier is not allow to edit/delete regardless permission
        if ($this->isOccupier) {
            if ($toolbarButtons->offsetExists('edit')) {
                unset($toolbarButtons['edit']);
            }

            if ($toolbarButtons->offsetExists('remove')) {
                unset($toolbarButtons['remove']);
            }
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $model = $this->_table;
        $buttons = $model->onUpdateActionButtons($event, $entity, $buttons);
        // Occupier is not allow to edit/delete regardless permission
        if ($this->isOccupier) {
            if (array_key_exists('edit', $buttons)) {
                unset($buttons['edit']);    //remove edit action from the action button
            }

            if (array_key_exists('remove', $buttons)) {
                unset($buttons['remove']);  // remove delete action from the action button
            }
        }

        return $buttons;
    }

    public function beforeAction(Event $event) {
        $model = $this->_table;

        $session = $model->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $AcademicPeriods->getCurrent();

        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $isOwnerCount = $InstitutionShifts->isOwner($institutionId, $academicPeriodId);
        $isOccupierCount = $InstitutionShifts->isOccupier($institutionId, $academicPeriodId);

        if ($isOwnerCount) {
            $this->isOwner = true;
        }

        if ($isOccupierCount) {
            $this->isOccupier = true;
        }
    }

    public function indexAfterAction(Event $event, $data) {
        $model = $this->_table;
        $session = $model->request->session();

        if ($this->isOccupier && (!is_null($session->read('Institution.Institutions.warning')))) {
            $model->Alert->warning('InstitutionInfrastructures.occupierAddNotAllowed');
            $session->delete('Institution.Institutions.warning');
        } else if ($this->isOwner == false && $this->isOccupier == false && (!is_null($session->read('Institution.Institutions.warning')))) {
            $model->Alert->warning('InstitutionInfrastructures.ownerAddNotAllowed');
            $session->delete('Institution.Institutions.warning');
        }
    }

    public function addBeforeAction(Event $event) {
        $model = $this->_table;
        $session = $model->request->session();

        if ($this->isOccupier || ($this->isOwner == false && $this->isOccupier == false)) {
            $session->write('Institution.Institutions.warning', 'warning');
            $url = $model->ControllerAction->url('index');
            $event->stopPropagation();
            $model->controller->redirect($url);
        }
    }

    public function getOwnerInstitutionId() {
        if ($this->isOccupier) {
           $model = $this->_table;

            $session = $model->request->session();
            $institutionId = $session->read('Institution.Institutions.id');

            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodId = $AcademicPeriods->getCurrent();

            $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');

            $conditions = [
                [$InstitutionShifts->aliasField('academic_period_id') => $academicPeriodId],
                [$InstitutionShifts->aliasField('location_institution_id') => $institutionId]
            ];

            $query = $InstitutionShifts
                ->find()
                ->where($conditions)
                ->toArray()
            ;

            $ownerInstitutionId = [];
            foreach ($query as $key => $value) {
                $ownerInstitutionId[$key] = $value['institution_id'];
            }

            return($ownerInstitutionId);
        }
    }
}
