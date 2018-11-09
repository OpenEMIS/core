<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;

class InfrastructureShiftBehavior extends Behavior
{
    private $isOwner = false;
    private $isOccupier = false;

    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateActionButtons'] = ['callable' => 'onUpdateActionButtons', 'priority' => 100];
        $events['Model.isRecordExists'] = 'isRecordExists';
        $events['ControllerAction.Model.beforeAction'] = 'beforeAction';
        $events['ControllerAction.Model.index.afterAction'] = 'indexAfterAction';
        $events['ControllerAction.Model.add.beforeAction'] = 'addBeforeAction';
        $events['ControllerAction.Model.edit.beforeAction'] = 'editBeforeAction';
        $events['ControllerAction.Model.delete.beforeAction'] = 'deleteBeforeAction';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        return $events;
    }

    public function isRecordExists(Event $event)
    {
        $callable = function ($model, $params) {
            return true;
        };
        return $callable;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;

        $session = $model->request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $AcademicPeriods->getCurrent();

        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $isOwnerCount = $InstitutionShifts->isOwner($institutionId, $academicPeriodId);
        $isOccupierCount = $InstitutionShifts->isOccupier($institutionId, $academicPeriodId);

        if ($this->isOccupier) {
            $toolbarButtons = $extra['toolbarButtons'];
            if (isset($toolbarButtons['edit'])) {
                unset($toolbarButtons['edit']);
            }

            if (isset($toolbarButtons['remove'])) {
                unset($toolbarButtons['remove']);
            }
        }

        if ($isOwnerCount) {
            $this->isOwner = true;
        }

        if ($isOccupierCount) {
            $this->isOccupier = true;
        }
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $model = $this->_table;
        $session = $model->request->session();

        $sessionKey = $model->registryAlias() . '.warning';
        if ($session->check($sessionKey)) {
            $messageKey = $session->read($sessionKey);
            $model->Alert->warning($messageKey);
            $session->delete($sessionKey);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->isOccupier) {
            $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
            unset($toolbarButtonsArray['edit']);
            unset($toolbarButtonsArray['remove']);
            $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        }
    }

    private function isAcademicInstitution()
    {
        $session = $this->_table->request->session();
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $institutionId = $session->read('Institution.Institutions.id');
        $classification = $InstitutionsTable->get($institutionId)->classification;
        return $classification == $InstitutionsTable::ACADEMIC;
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $session = $model->request->session();
        $sessionKey = $model->registryAlias() . '.warning';

        if ($this->isOccupier) {
            $session->write($sessionKey, 'InstitutionInfrastructures.occupierAddNotAllowed');
            $url = $model->url('index');
            $event->stopPropagation();
            return $model->controller->redirect($url);
        } else if ($this->isOwner == false && $this->isOccupier == false && $this->isAcademicInstitution()) {
            $session->write($sessionKey, 'InstitutionInfrastructures.ownerAddNotAllowed');
            $url = $model->url('index');
            $event->stopPropagation();
            return $model->controller->redirect($url);
        }
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $session = $model->request->session();
        $sessionKey = $model->registryAlias() . '.warning';

        if ($this->isOccupier || ($this->isOwner == false && $this->isOccupier == false && $this->isAcademicInstitution())) {
            $session->write($sessionKey, 'InstitutionInfrastructures.occupierEditNotAllowed');
            $url = $model->url('index');
            $event->stopPropagation();
            return $model->controller->redirect($url);
        }
    }

    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $session = $model->request->session();
        $sessionKey = $model->registryAlias() . '.warning';

        if ($this->isOccupier || ($this->isOwner == false && $this->isOccupier == false && $this->isAcademicInstitution())) {
            $session->write($sessionKey, 'InstitutionInfrastructures.occupierDeleteNotAllowed');
            $url = $model->url('index');
            $event->stopPropagation();
            return $model->controller->redirect($url);
        }
    }

    public function getOwnerInstitutionId()
    {
        $ownerInstitutionIds = [];

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

            $ownerInstitutionIds = $InstitutionShifts
                ->find('list', ['keyField' => 'institution_id', 'valueField' => 'institution_id'])
                ->where($conditions)
                ->toArray();
        }

        return($ownerInstitutionIds);
    }
}
