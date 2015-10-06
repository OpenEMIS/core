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
		'prefix_key' => '',
		'prefix' => ''
	];

	public function initialize(array $config) {
		if (empty($this->config('plugin'))) {
			$exploded = explode('.', $this->_table->registryAlias());
			if (count($exploded)==2) {
				$this->config('plugin', $exploded[0]);
			}
		}
		if (empty($this->config('model'))) {
			$this->config('model', Inflector::pluralize($this->config('plugin')));
		}
		if (empty($this->config('prefix_key'))) {
			$this->config('prefix_key', strtolower(Inflector::singularize($this->config('model'))).'_prefix');
		}

		$prefix = TableRegistry::get('ConfigItems')->value($this->config('prefix_key'));
		$prefix = explode(",", $prefix);
		$prefix = (isset($prefix[1]) && $prefix[1]>0) ? $prefix[0] : '';

		$this->config('prefix', $prefix);
	}
	
	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->openemis_no;
	}

	public function getNewOpenEmisNo($importedUniqueCodes, $row) {
		$prefix = $this->config('prefix');

		$importedCodes = $importedUniqueCodes->getArrayCopy();
		if (count($importedCodes)>0) {
			$val = reset($importedCodes);
			$val = $prefix . (intval(substr($val, strlen($prefix))) + $row);
			// usleep(10000);
		} else {
			$val = TableRegistry::get('User.Users')->getUniqueOpenemisId(['model' => $this->config('plugin')]);
		}

		// if (in_array($val, $importedUniqueCodes->getArrayCopy())) {
		// 	// minimum pause time needed to allow this upload script to run without http request timeout error
		// 	usleep(35000);
		// 	$generatedId = $prefix . (intval(substr($val, strlen($prefix))) + rand());
		// 	$val = $this->getNewOpenEmisNo($importedUniqueCodes, $generatedId, $prefix);
		// }
		return $val;
	}

}
