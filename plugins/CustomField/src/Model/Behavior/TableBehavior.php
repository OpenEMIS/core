<?php
namespace CustomField\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\Event;

class TableBehavior extends Behavior {
	public function initialize(array $config) {
		parent::initialize($config);
		if (isset($config['setup']) && $config['setup'] == true) {
			$this->_table->ControllerAction->addField('tables', [
	            'type' => 'element',
	            'order' => 5,
	            'element' => 'CustomField.CustomFields/table',
	            'visible' => true
	        ]);
		}
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

	public function onGetCustomTableElement(Event $event, $action, $entity, $attr, $options=[]) {
        $value = '';

        $CustomTableCells = $this->_table->CustomTableCells;

		$customField = $attr['customField'];
		$form = $event->subject()->Form;

		$tableHeaders = [];
		$tableCells = [];
		$cellCount = 0;
		foreach ($customField->custom_table_rows as $rowKey => $rowObj) {
			$rowData = [];
			$rowData[] = $rowObj->name;

			$colCount = 0;
			foreach ($customField->custom_table_columns as $colKey => $colObj) {
				if (!array_key_exists($colObj->id, $tableHeaders)) {
					$tableHeaders[$colObj->id] = $colObj->name;
				}
				if ($colCount++ == 0) {continue;}

				$fieldId = $attr['customField']->id;
				$tableColumnId = $colObj->id;
				$tableRowId = $rowObj->id;

				$fieldPrefix = $attr['model'] . '.custom_table_cells.' . $cellCount++;
				$cellInput = "";
				$cellValue = "";

				$cellOptions = ['label' => false, 'value' => ''];
				if (isset($entity->id)) {
					$results = $CustomTableCells
						->find('all')
						->select([
							$CustomTableCells->aliasField('id'),
							$CustomTableCells->aliasField('text_value'),
						])
						->where([
							$CustomTableCells->aliasField($attr['fieldKey']) => $fieldId,
							$CustomTableCells->aliasField($attr['tableColumnKey']) => $tableColumnId,
							$CustomTableCells->aliasField($attr['tableRowKey']) => $tableRowId,
							$CustomTableCells->aliasField($attr['recordKey']) => $entity->id
						])
						->all();

					if (!$results->isEmpty()) {
						$data = $results
							->first()
							->toArray();

						$cellValue = $data['text_value'];
						$cellOptions['value'] = $cellValue;
						$cellInput .= $form->hidden($fieldPrefix.".id", ['value' => $data['id']]);
					}
				}

				$cellInput .= $form->input($fieldPrefix.".text_value", $cellOptions);
				$cellInput .= $form->hidden($fieldPrefix.".".$attr['fieldKey'], ['value' => $fieldId]);
				$cellInput .= $form->hidden($fieldPrefix.".".$attr['tableColumnKey'], ['value' => $tableColumnId]);
				$cellInput .= $form->hidden($fieldPrefix.".".$attr['tableRowKey'], ['value' => $tableRowId]);				

				if ($action == 'view') {
					$rowData[$colKey] = $cellValue;
				} else if ($action == 'edit') {
					$rowData[$colKey] = $cellInput;
				}
			}

			$tableCells[$rowKey] = $rowData;
		}

    	$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;


		if ($action == 'view') {
			$value = $event->subject()->renderElement('CustomField.table', ['attr' => $attr]);
		} else if ($action == 'edit') {
			$value = $event->subject()->renderElement('CustomField.table', ['attr' => $attr]);	
		}

        return $value;
    }
}
