<?php
namespace OpenEmis\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;

/**
 * OpenEmis SectionBehavior
 *
 * This file is to render input element as a section separator in forms.
 * *Depends on ControllerAction Component
 *
 * Usage:
 * Firstly, add this behavior in model's initialize function.
 *
 * public function initialize(array $config) {
 * 		.............
 *
 *   	$this->addBehavior('OpenEmis.Section');
 *
 * 		.............
 * }
 *
 *
 * Secondly, defines the field in model's beforeAction()
 *
 *	public function beforeAction($event) {
 * 		.............
 *
 * 		$this->ControllerAction->field('information_section', ['type' => 'section']);
 *
 * 		.............
 * 	}
 *
 *
 * Field declaration with custom title on the element.
 *
 * 		$this->ControllerAction->field('information_section', ['type' => 'section', 'title' => $title_val]);
 *
 * If the 'title' parameter is not defined in the field declaration,
 * the humanized version of the field name will be used.
 *
 */
class SectionBehavior extends Behavior {
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'ControllerAction.Model.index.afterAction' 		=> 'indexAfterAction',
			'ControllerAction.Model.view.afterAction' 		=> ['callable' => 'viewAfterAction', 'priority' => 200],
			'ControllerAction.Model.addEdit.afterAction' 	=> ['callable' => 'addEditAfterAction', 'priority' => 200]
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function indexAfterAction(Event $event, $data) {
		$this->_fieldSetup();
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->_fieldSetup();
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->_fieldSetup();
	}

	private function _fieldSetup() {
		foreach ($this->_table->fields as $key=>$value) {
			if (array_key_exists('type', $value) && $value['type'] == 'section') {
				$this->_table->fields[$key]['override'] = true;
				$this->_table->fields[$key]['label'] = false;
				$this->_table->fields[$key]['rowClass'] = 'section-header';
			}
		}
	}

	public function onGetSectionElement(Event $event, $action, Entity $entity, $attr, $options) {
		$html = '';

		if (!array_key_exists('title', $attr)) {
			$attr['title'] = __(Inflector::humanize($attr['field']));
		}

		if ($action == 'view') {
			$html .= $attr['title'];
		} else if ($action == 'add' || $action == 'edit') {
			$html .= '<div class="section-header">'. $attr['title'] .'</div>';
			$html .= '<div class="clearfix">&nbsp;</div>';
		}

		return $html;
	}
}
