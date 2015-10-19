<?php
namespace Import\Model\Behavior;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
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

	public function initialize(array $config) {
		$plugin = $this->config('plugin');
		if (empty($plugin)) {
			$exploded = explode('.', $this->_table->registryAlias());
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
		$prefix = TableRegistry::get('ConfigItems')->value($prefix_key);
		$prefix = explode(",", $prefix);
		$prefix = (isset($prefix[1]) && $prefix[1]>0) ? $prefix[0] : '';
		$this->config('prefix', $prefix);

	    // register the Users table once
		$this->Users = TableRegistry::get('User.Users');
	}
	
	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
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

	public function onImportPopulateDirectTableData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $lookupColumn, ArrayObject $data) {
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

		$translatedReadableCol = $this->_table->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			try {
				$modelData = $modelData->toArray();
			} catch (\Exception $e) {
				pr($modelData->sql());die;
			}
			foreach($modelData as $row) {
				$data[$sheetName][] = [
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}

}
