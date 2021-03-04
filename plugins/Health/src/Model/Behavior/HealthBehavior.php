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

        if ($name == 'Students' && $controller->AccessControl->check(['StudentBodyMasses', 'index'])) {
            $session = $this->_table->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $params = $this->_table->paramsEncode(['id' => $institutionId]);

            $tabElements['BodyMasses'] = [
                'url' => ['plugin' => 'Institution', 'institutionId' => $params, 'controller' => 'StudentBodyMasses', 'action' => 'index'],
                'text' => __('Body Mass')
            ];
        } elseif ($name == 'Staff' && $controller->AccessControl->check(['StaffBodyMasses', 'index'])) {
            $session = $this->_table->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $params = $this->_table->paramsEncode(['id' => $institutionId]);

            $tabElements['BodyMasses'] = [
                'url' => ['plugin' => 'Institution', 'institutionId' => $params, 'controller' => 'StaffBodyMasses', 'action' => 'index'],
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

        if ($name == 'Students' && $controller->AccessControl->check(['StudentInsurances', 'index'])) {
            $session = $this->_table->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $params = $this->_table->paramsEncode(['id' => $institutionId]);

            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Institution', 'institutionId' => $params, 'controller' => 'StudentInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
        } elseif ($name == 'Staff' && $controller->AccessControl->check(['StaffInsurances', 'index'])) {
            $session = $this->_table->request->session();
            $institutionId = $session->read('Institution.Institutions.id');
            $params = $this->_table->paramsEncode(['id' => $institutionId]);

            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Institution', 'institutionId' => $params, 'controller' => 'StaffInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
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
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
        $controller->set('selectedAction', $model->alias());
    }
}
