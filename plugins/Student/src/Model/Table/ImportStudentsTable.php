<?php
namespace Student\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Collection\Collection;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class ImportStudentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        // $this->addBehavior('Import.Import', ['plugin'=>'Student', 'model'=>'Students']);
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
		$institution = $model->find()->where(['openemis_no'=>$code])->first();
		if (!$institution) {
			$tempRow['entity'] = $model->newEntity();
			$tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes);
		} else {
			$tempRow['entity'] = $institution;
		}
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->openemis_no;
	}

	protected function getNewOpenEmisNo($importedUniqueCodes, $generatedId=null, $prefix='') {
		if (!is_null($generatedId)) {
			$val = $generatedId;
		} else {
			$prefix = TableRegistry::get('ConfigItems')->value('students_prefix');
			$prefix = explode(",", $prefix);
			$prefix = ($prefix[1] > 0)? $prefix[0]: '';
			$val = TableRegistry::get('User.Users')->getUniqueOpenemisId(['model' => 'Student']);
		}
		if (in_array($val, $importedUniqueCodes)) {
			$generatedId = $prefix . (intval(substr($val, strlen($prefix))) + rand());
			$val = $this->getNewOpenEmisNo($importedUniqueCodes, $generatedId);
		}
		return $val;
	}

}
