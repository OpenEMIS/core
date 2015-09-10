<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Cache\Cache;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Filesystem\Folder;
use Cake\Network\Request;
use Cake\ORM\Query;
use ArrayObject;

class LabelsTable extends AppTable {

	private $excludeList = ['created_user_id', 'created', 'modified_user_id', 'modified'];
	private $defaultConfig = 'labels';

	public function getLabel($module, $field, $language) {
		$label = false;
		$keyFetch = $module.'.'.$field;
		$label = Cache::read($keyFetch, $this->defaultConfig);

		if ($label !== false) {
			$label =  __(ucfirst($label));
		} else {
			//check whether the key is part of the excluded list
			if(in_array($field, $this->excludeList))
				$label = Cache::read('General.'.$field, $this->defaultConfig);
		}

		return $label;
	}

	public function storeLabelsInCache() {
		// Will clear all keys.
		//Cache::clear(false);
		
		$cacheFolder = new Folder(CACHE.'labels');
		$files = $cacheFolder->find();
		if(empty($files)) {
			$keyArray = [];
			$allLabels = $this->find();
			foreach($allLabels as $eachLabel) {
				$keyCreation = $eachLabel->module.'.'.$eachLabel->field;
				$keyValue = self::concatenateLabel($eachLabel);
				$keyArray[$keyCreation] = $keyValue;	
			}	

			//Write multiple to cache
			$result = Cache::writeMany($keyArray, $this->defaultConfig);
		}
	}

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('module_name', ['type' => 'readonly']);
		$this->ControllerAction->field('field_name', ['type' => 'readonly']);
	}	

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$keyFetch = $entity->module.'.'.$entity->field;
		$keyValue = self::concatenateLabel($entity);
		Cache::write($keyFetch, $keyValue, $this->defaultConfig);
	}	

	public function beforeAction(Event $event){
		$this->ControllerAction->field('module', ['visible' => false]);
		$this->ControllerAction->field('field', ['visible' => false]);
		$this->ControllerAction->field('visible', ['visible' => false]);
		$this->ControllerAction->field('created', ['visible' => false]);
		$this->ControllerAction->field('created_user_id', ['visible' => false]);
	}

	public function concatenateLabel($entity){
		$keyFetch = $entity->module.'.'.$entity->field;
		$keyValue = (!is_null($entity->name) && ($entity->name != "")) ? $entity->name : $entity->field_name;

		if(!is_null($entity->code) && ($entity->code != ""))
			$keyValue = '('.ucfirst($entity->code).') '.ucfirst($keyValue);

		return $keyValue;
	}

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		//do not save empty strings
		if($entity->code == "")
			$entity->code = null;

		if($entity->name == "")
			$entity->name = null;
	}	

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->where(['visible' => 1]);
	}

	public function getDefaultConfig(){
		return $this->defaultConfig;
	}

	public function validationDefault(Validator $validator) {
		$validator
			->add('code', [
					'ruleUnique' => [
						'rule' => 'validateUnique',
						'provider' => 'table',
					]
				])
			;
		return $validator;
	}
}
