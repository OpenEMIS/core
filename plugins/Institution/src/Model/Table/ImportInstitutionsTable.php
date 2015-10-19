<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportInstitutionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

	    $this->addBehavior('Import.Import');

	    // register the target table once
	    $this->Institutions = TableRegistry::get('Institution.Institutions');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
			'Model.import.onImportPopulateDirectTableData' => 'onImportPopulateDirectTableData',
			'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {
		$columns = new Collection($columns);
		$filtered = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'code';
		});
		$codeIndex = key($filtered->toArray());
		$code = $sheet->getCellByColumnAndRow($codeIndex, $row)->getValue();

		if (in_array($code, $importedUniqueCodes->getArrayCopy())) {
			$tempRow['duplicates'] = true;
			return true;
		}

		$institution = $this->Institutions->find()->where(['code'=>$code])->first();
		if (!$institution) {
			$tempRow['entity'] = $this->Institutions->newEntity();
		} else {
			$tempRow['entity'] = $institution;
		}
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->code;
	}

	public function onImportPopulateDirectTableData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		if ($lookupModel == 'Areas') {
			$order = [$lookupModel.'.area_level_id', $lookupModel.'.order'];
		} else if ($lookupModel == 'AreaAdministratives') {
			$order = [$lookupModel.'.area_administrative_level_id', $lookupModel.'.order'];
		} else {
			$order = [$lookupModel.'.order'];
		}

		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$selectFields = ['name', $lookupColumn];
		$modelData = $lookedUpTable->find('all')
			->select($selectFields)
			;
		if ($lookedUpTable->hasField('order')) {
			$modelData->order($order);
		}

		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$sheetName][] = [
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		return true;
	}
}
