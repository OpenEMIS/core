<?php
namespace Staff\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportStaffTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        $this->addBehavior('Import.Import', ['model'=>'Staff']);
	    $this->addBehavior('Import.ImportUser', ['model'=>'Staff']);

	    // register the target table once 
	    $this->Staff = TableRegistry::get('Staff.Staff');
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

		$user = $this->Staff->find()->where(['openemis_no'=>$code])->first();
		if (!$user) {
			$tempRow['entity'] = $this->Staff->newEntity();
			$tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row);
		} else {
			$tempRow['entity'] = $user;
		}
		$tempRow['is_staff'] = 1;
	}

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		return true;
	}
}
