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
		$CustomTableCells = $this->_table->CustomTableCells;
		$tableCells = $CustomTableCells
			->find('all')
			->select([
				$CustomTableCells->aliasField('id'),
				$CustomTableCells->aliasField('text_value'),
				$CustomTableCells->aliasField('custom_table_column_id'),
				$CustomTableCells->aliasField('custom_table_row_id'),
			])
			->where([
				$CustomTableCells->aliasField('custom_field_id') => $attr['customField']->id,
				$CustomTableCells->aliasField('custom_record_id') => $entity->id
			])
			->all()
			->toArray();

		//$data = $query->toArray();

		// pr($data);
		// pr($entity->id);
		// pr($attr['customField']->id);
		// pr($attr);

        $value = '';

        if ($action == 'index' || $action == 'view') {
        	$value = $event->subject()->renderElement('CustomField.table', ['attr' => $attr]);
        } else if ($action == 'edit') {
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
					$cellData = "";
					$cellOptions = ['label' => false, 'value' => ''];
					if (isset($entity->id)) {
						$results = $CustomTableCells
							->find('all')
							->select([
								$CustomTableCells->aliasField('id'),
								$CustomTableCells->aliasField('text_value'),
							])
							->where([
								$CustomTableCells->aliasField('custom_field_id') => $fieldId,
								$CustomTableCells->aliasField('custom_table_column_id') => $tableColumnId,
								$CustomTableCells->aliasField('custom_table_row_id') => $tableRowId,
								$CustomTableCells->aliasField('custom_record_id') => $entity->id
							])
							->all();

						if (!$results->isEmpty()) {
							$data = $results
								->first()
								->toArray();

							$cellOptions['value'] = $data['text_value'];
							$cellData .= $form->hidden($fieldPrefix.".id", ['value' => $data['id']]);
						}
					}

					$cellData .= $form->input($fieldPrefix.".text_value", $cellOptions);
					$cellData .= $form->hidden($fieldPrefix.".custom_field_id", ['value' => $fieldId]);
					$cellData .= $form->hidden($fieldPrefix.".custom_table_column_id", ['value' => $tableColumnId]);
					$cellData .= $form->hidden($fieldPrefix.".custom_table_row_id", ['value' => $tableRowId]);

					$rowData[$colKey] = $cellData;
				}

				$tableCells[$rowKey] = $rowData;
			}

        	$attr['tableHeaders'] = $tableHeaders;
        	$attr['tableCells'] = $tableCells;

        	$value = $event->subject()->renderElement('CustomField.table', ['attr' => $attr]);
        }

        return $value;
    }
}
