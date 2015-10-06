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
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.custom.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.custom.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
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

		// $tempRow['entity'] must be assigned!!!
		$model = TableRegistry::get('Student.Students');
		$user = $model->find()->where(['openemis_no'=>$code])->first();
		if (!$user) {
			$tempRow['entity'] = $model->newEntity();
			$tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row);
			$tempRow['is_student'] = 1;
		} else {
			$tempRow['entity'] = $user;
		}
	}

}
