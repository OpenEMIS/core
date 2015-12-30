<?php
namespace Health\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class HealthBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.beforeAction'] 			= ['callable' => 'beforeAction', 'priority' => 100];
		return $events;
	}

	public function beforeAction(Event $event) {
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
                'text' => __('Immunizations')
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

		$controller->set('tabElements', $tabElements);
		$controller->set('selectedAction', $model->alias());
	}
}
