<?php
namespace Student\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportStudentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

	    $this->addBehavior('Import.Import');
	    $this->addBehavior('Import.ImportUser');

	    // register the target table once 
	    $this->Students = TableRegistry::get('Student.Students');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
			'Model.import.onImportPopulateAreaAdministrativesData' => 'onImportPopulateAreaAdministrativesData',
			'Model.import.onImportPopulateGendersData' => 'onImportPopulateGendersData',
			'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {
		$columns = new Collection($columns);
		$filtered = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'openemis_no';
		});
		$codeIndex = key($filtered->toArray());
		$code = $sheet->getCellByColumnAndRow($codeIndex, $row)->getValue();

		if (in_array($code, $importedUniqueCodes->getArrayCopy())) {
			$tempRow['duplicates'] = true;
			return true;
		}

		$user = $this->Students->find()->where(['openemis_no'=>$code])->first();
		if (!$user) {
			$tempRow['entity'] = $this->Students->newEntity();
			$tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row);
		} else {
			$tempRow['entity'] = $user;
		}
		$tempRow['is_student'] = 1;
	}

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		return true;
	}
}
