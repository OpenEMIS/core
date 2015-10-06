<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Collection\Collection;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class ImportStudentAttendancesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        // $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'Institutions']);
	    $this->addBehavior('Import.Import');
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
		    return $value == 'code';
		});
		$codeIndex = key($filtered->toArray());
		$code = $sheet->getCellByColumnAndRow($codeIndex, $row)->getValue();

		if (in_array($code, $importedUniqueCodes->getArrayCopy())) {
			$tempRow['duplicates'] = true;
			return true;
		}

		// $tempRow['entity'] must be assigned!!!
		$model = TableRegistry::get('Institution.Institutions');
		$institution = $model->find()->where(['code'=>$code])->first();
		if (!$institution) {
			$tempRow['entity'] = $model->newEntity();
		} else {
			$tempRow['entity'] = $institution;
		}
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->code;
	}

}
