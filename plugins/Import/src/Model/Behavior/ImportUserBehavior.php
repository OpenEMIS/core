<?php
namespace Import\Model\Behavior;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class ImportUserBehavior extends Behavior {
	protected $_defaultConfig = [
		'plugin' => '',
		'model' => '',
		'prefix' => ''
	];

	public function initialize(array $config): void // POCOR-8683
    {
		$plugin = $this->config('plugin');
		if (empty($plugin)) {
			$exploded = explode('.', $this->_table->getRegistryAlias());
			if (count($exploded)==2) {
				$this->config('plugin', $exploded[0]);
			}
		}
		$plugin = $this->config('plugin');
		$model = $this->config('model');
		if (empty($model)) {
			$this->config('model', Inflector::pluralize($plugin));
		}
		$model = $this->config('model');

		$prefix_key = strtolower(Inflector::singularize($model)).'_prefix';
		$prefix = TableRegistry::getTableLocator()->get('Configuration.ConfigItems')->value($prefix_key);
		$prefix = explode(",", $prefix);
		$prefix = (isset($prefix[1]) && $prefix[1]>0) ? $prefix[0] : '';
		$this->setConfig('prefix', $prefix);

	    // register the Users table once
		$this->Users = TableRegistry::getTableLocator()->get('User.Users');
	}

	public function onImportUpdateUniqueKeys(EventInterface $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->openemis_no;
	}

	public function getNewOpenEmisNo($importedUniqueCodes, $row) {
		$importedCodes = $importedUniqueCodes->getArrayCopy();
		if (count($importedCodes)>0) {
			$prefix = $this->config('prefix');
			$val = reset($importedCodes);
			$val = $prefix . (intval(substr($val, strlen($prefix))) + $row);
		} else {
			$model = $this->config('plugin');
			$val = $this->Users->getUniqueOpenemisId(['model' => $model]);
		}
		return $val;
	}

	public function onImportPopulateAreaAdministrativesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		if (!empty($data[$sheetName])) {
			return true;
		}

		$lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->find('all')
								->select(['name', $lookupColumn])
								->order($lookupModel.'.area_administrative_level_id', $lookupModel.'.order')
								;

		$translatedReadableCol = $this->_table->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$sheetName][] = [
					$row->name,
					$row->{$lookupColumn}
				];
			}
		}
	}

	public function onImportPopulateGendersData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->find('all')
								->select(['name', $lookupColumn])
								->order([$lookupModel.'.order'])
								;

		$translatedReadableCol = $this->_table->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$sheetName][] = [
					$row->name,
					$row->{$lookupColumn}
				];
			}
		}
	}

}
