<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

namespace ControllerAction\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Event\Event;

class FieldOptionBehavior extends Behavior {
	public function initialize(array $config) {
		$this->_table->table('field_option_values');
	}

	public function getDefaultValue() {
		$value = '';
		$primaryKey = $this->_table->primaryKey();
		$entity = $this->getDefaultEntity();
		return $entity->$primaryKey;
	}

	public function getDefaultEntity() {
		if ($this->_table->table() != 'field_option_values') {
			$query = $this->_table->find();
			$entity = $query
				->where([$this->_table->aliasField('default') => 1])
				->first();

			if (is_null($entity)) {
				$query = $this->_table->find();
				$entity = $query
					->first();
			}
		} else {
			$entity = $this->_table
				->find()
				->innerJoin(
					['FieldOption' => 'field_options'],
					[
						'FieldOption.id = ' . $this->_table->aliasField('field_option_id'),
						'FieldOption.code' => $this->_table->alias()
					]
				)
				->find('order')->find('visible')
				->where([$this->_table->aliasField('default') => 1])
				->first();

			if (is_null($entity)) {
				$entity = $this->_table
					->find()
					->innerJoin(
						['FieldOption' => 'field_options'],
						[
							'FieldOption.id = ' . $this->_table->aliasField('field_option_id'),
							'FieldOption.code' => $this->_table->alias()
						]
					)
					->find('order')->find('visible')
					->first();
			}
		}
		return $entity;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction'];
		$events['ControllerAction.Model.index.beforeAction'] = ['callable' => 'indexBeforeAction'];
		return $events;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		// only perform for v4
		if ($this->_table->hasBehavior('ControllerAction')) {
			if ($entity->has('default') && $entity->default == 1) {
				$this->_table->updateAll(['default' => 0], [$this->_table->primaryKey().' != ' => $entity->{$this->_table->primaryKey()}]);
			}
		}
	}

	private function buildFieldOptions() {
		$data = $this->_table->FieldOption->getFieldOptions();
		$fieldOptions = [];
		foreach ($data as $key => $obj) {
			$parent = __($obj['parent']);
			if (!array_key_exists($parent, $fieldOptions)) {
				$fieldOptions[$parent] = [];
			}
			$keyName = Inflector::humanize(Inflector::underscore($key));
			$fieldOptions[$parent][$key] = __($keyName);
		}
		return $fieldOptions;
	}

	private function checkFieldOption($event, $selected) {
		if (!$this->_table->request->is('ajax')) { // to work with reorder
			$FieldOptions = TableRegistry::get('FieldOption.FieldOptions');
			$entity = $FieldOptions->get($selected);

			if ($entity->code != $this->_table->alias) {
				$event->stopPropagation();
				return $this->_table->controller->redirect(['action' => 'index', 'field_option_id' => $selected]);
			}
		}
	}

	private function addFieldOptionControl(ArrayObject $extra, $data = []) {
		$extra['elements']['controls'] = ['name' => 'FieldOption.controls', 'data' => $data, 'order' => 2];
	}

	// for CA v4
	public function onGetEditable(Event $event, Entity $entity) {
		return $entity->editable == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function onGetDefault(Event $event, Entity $entity) {
		return $entity->default == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$model = $this->_table;
		$fieldOptions = $this->buildFieldOptions();
		$selectedOption = $model->alias;
		$this->addFieldOptionControl($extra, ['fieldOptions' => $fieldOptions, 'selectedOption' => $selectedOption]);

		// $this->checkFieldOption($event, $selectedOption); // deprecated

		$model->field('default', ['options' => $model->getSelectOptions('general.yesno'), 'after' => 'visible']);
		$model->field('editable', ['options' => $model->getSelectOptions('general.yesno'), 'visible' => ['index' => true], 'after' => 'default']);

		$extra['config']['selectedLink'] = ['controller' => 'FieldOptions', 'action' => 'index'];
	}

	public function indexBeforeAction(Event $event) {
		$model = $this->_table;
		$model->field('name', ['after' => 'editable']);
		$fields = ['visible', 'default', 'editable', 'name', 'international_code', 'national_code'];
		foreach ($fields as $field) {
			if (array_key_exists($field, $model->fields)) {
				if (is_array($model->fields[$field]['visible'])) {
					$model->fields[$field]['visible']['index'] = true;
				} else {
					if ($model->fields[$field]['visible']) {
						$model->fields[$field]['visible'] = [
							'view' => true,
							'edit' => true,
							'index' => true
						];
					} else {
						$model->fields[$field]['visible'] = ['index' => true];
					}
				}
			}
		}
	}
}
