<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class TableBehavior extends Behavior {
	public function initialize(array $config) {
		$this->_table->ControllerAction->addField('tables', [
            'type' => 'element',
            'order' => 5,
            'element' => 'CustomField.table',
            'visible' => true
        ]);
    }

	public function addEditOnAddColumn(Event $event, Entity $entity, array $data, array $options) {
		$columnOptions = [
			'name' => '',
			'visible' => 1
		];
		$data[$this->_table->alias()]['custom_table_columns'][] = $columnOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'CustomTableColumns' => ['validate' => false],
			'CustomTableRows' => ['validate' => false]
		];

		return compact('entity', 'data', 'options');
	}

	public function addEditOnAddRow(Event $event, Entity $entity, array $data, array $options) {
		$rowOptions = [
			'name' => '',
			'visible' => 1
		];
		$data[$this->_table->alias()]['custom_table_rows'][] = $rowOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'CustomTableColumns' => ['validate' => false],
			'CustomTableRows' => ['validate' => false]
		];

		return compact('entity', 'data', 'options');
	}
}
