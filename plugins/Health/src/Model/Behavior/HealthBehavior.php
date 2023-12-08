<?php

namespace Health\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class HealthBehavior extends Behavior
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 100];
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $controller = $this->_table->controller;
        $model = $this->_table;
        $plugin = $controller->plugin;
        $name = $controller->name;
        $institutionId = null;
        $encodedInstitutionID = null;
        if ($name == 'Students' || $name == 'Staff') {
            $institutionId = $this->getInstitutionID();
            $encodedInstitutionID = $this->_table->paramsEncode(['id' => $institutionId]);
        }
        $tabElements = [];
        if ($controller->AccessControl->check([$name, 'Healths', 'index'])) {
            $tabElements['Healths'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'Healths'],
                'text' => __('Overview')
            ];
        }

        if ($controller->AccessControl->check([$name, 'HealthAllergies', 'index'])) {
            $tabElements['Allergies'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'HealthAllergies'],
                'text' => __('Allergies')
            ];
        }

        if ($controller->AccessControl->check([$name, 'HealthConsultations', 'index'])) {
            $tabElements['Consultations'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'HealthConsultations'],
                'text' => __('Consultations')
            ];
        }

        if ($controller->AccessControl->check([$name, 'HealthFamilies', 'index'])) {
            $tabElements['Families'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'HealthFamilies'],
                'text' => __('Families')
            ];
        }

        if ($controller->AccessControl->check([$name, 'HealthHistories', 'index'])) {
            $tabElements['Histories'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'HealthHistories'],
                'text' => __('Histories')
            ];
        }

        if ($controller->AccessControl->check([$name, 'HealthImmunizations', 'index'])) {
            $tabElements['Immunizations'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'HealthImmunizations'],
                //'text' => __('Immunizations')
                'text' => __('Vaccinations')
            ];
        }

        if ($controller->AccessControl->check([$name, 'HealthMedications', 'index'])) {
            $tabElements['Medications'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'HealthMedications'],
                'text' => __('Medications')
            ];
        }

        if ($controller->AccessControl->check([$name, 'HealthTests', 'index'])) {
            $tabElements['Tests'] = [
                'url' => ['plugin' => $plugin, 'controller' => $name, 'action' => 'HealthTests'],
                'text' => __('Tests')
            ];
        }

        if ($name == 'Students' && $controller->AccessControl->check([$name, 'StudentBodyMasses', 'index'])) {

            $tabElements['StudentBodyMasses'] = [
                'url' => ['plugin' => 'Student',
                    'institutionId' => $encodedInstitutionID,
                    'controller' => 'Students',
                    'action' => 'StudentBodyMasses'],
                'text' => __('Body Mass')
            ];
        } elseif ($name == 'Staff' && $controller->AccessControl->check([$name, 'StaffBodyMasses', 'index'])) {

            $tabElements['StaffBodyMasses'] = [
                'url' => ['plugin' => 'Staff',
                    'institutionId' => $encodedInstitutionID,
                    'controller' => 'Staff',
                    'action' => 'StaffBodyMasses'],
                'text' => __('Body Mass')
            ];
        } elseif ($name == 'Directories' && $controller->AccessControl->check(['DirectoryBodyMasses', 'index'])) {
            $tabElements['BodyMasses'] = [
                'url' => ['plugin' => 'Directory', 'controller' => 'DirectoryBodyMasses', 'action' => 'index'],
                'text' => __('Body Mass')
            ];
        } elseif ($name == 'Profiles' && $controller->AccessControl->check(['ProfileBodyMasses', 'index'])) {
            $tabElements['BodyMasses'] = [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileBodyMasses', 'action' => 'index'],
                'text' => __('Body Mass')
            ];
        }

        if ($name == 'Students' && $controller->AccessControl->check([$name, 'StudentInsurances', 'index'])) {

            $tabElements['StudentInsurances'] = [
                'url' => ['plugin' => 'Student', 'institutionId' => $encodedInstitutionID, 'controller' => 'Students', 'action' => 'StudentInsurances'],
                'text' => __('Insurances')
            ];
        } elseif ($name == 'Staff' && $controller->AccessControl->check([$name, 'StaffInsurances', 'index'])) {
            /*$tabElements['StaffInsurances'] = [
                'url' => ['plugin' => 'Staff', 'institutionId' => $params, 'controller' => 'Staff', 'action' => 'StaffInsurances'],
                'text' => __('Insurances'),
                'class' => 'tab-active'
            ];*/
            /*POCOR-6311 Starts*/
            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionID, 'controller' => 'StaffInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
            /*POCOR-6311 Ends*/
        } elseif ($name == 'Directories' && $controller->AccessControl->check(['DirectoryInsurances', 'index'])) {
            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Directory', 'controller' => 'DirectoryInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
        } elseif ($name == 'Profiles' && $controller->AccessControl->check(['ProfileInsurances', 'index'])) {
            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
        }
        foreach ($tabElements as &$n) {
            if (isset($n['url'])) {
                if ($encodedInstitutionID) {
                    $n['url']['institutionId'] = $encodedInstitutionID;
                }
            }
        }
        /*POCOR-6307 Starts*/
        $modelName = $model->alias();
        if ($name == 'Staff' && $model->alias() == 'UserInsurances') {
            $modelName = 'StaffInsurances';
        } elseif ($name == 'Students' && $model->alias() == 'UserBodyMasses') {
            $modelName = 'StudentBodyMasses';
        } elseif ($name == 'Students' && $model->alias() == 'UserInsurances') {
            $modelName = 'StudentInsurances';
        }
        /*POCOR-6307 Ends*/
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
        $controller->set('selectedAction', $modelName);
    }

    private function getInstitutionID()
    {
        $session = $this->_table->request->session();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->_table->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = isset($this->_table->request->params['institutionId']) ?
            $this->_table->request->params['institutionId'] :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->_table->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }
}
