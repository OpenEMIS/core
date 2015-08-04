<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Cake\Cache\Cache;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Filesystem\Folder;

class LabelsTable extends AppTable {

	private $excludeList = ['created_user_id', 'created', 'modified_user_id', 'modified'];

	public function getLabel($module, $field, $language) {
		$label = false;
		$keyFetch = $module.'.'.$field;
		$label = Cache::read($keyFetch);

		if ($label !== false) {
			$label =  __(ucfirst($label));
		} else {
			//check whether the key is part of the excluded list
			if(in_array($field, $this->excludeList))
				$label = Cache::read('General.'.$field);
		}

		return $label;
	}

	public function storeLabelsInCache() {
		// Will only clear expired keys.
		Cache::clear(false);
		
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
			$result = Cache::writeMany($keyArray);
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		unset($buttons['remove']);
		return $buttons;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if($action == 'index')
			unset($toolbarButtons['add']);
	}

	public function editBeforeAction(Event $event) {
		$this->ControllerAction->field('field_name', ['type' => 'readonly']);
	}	

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$keyFetch = $entity->module.'.'.$entity->field;
		$keyValue = self::concatenateLabel($entity);
		Cache::write($keyFetch, $keyValue);
	}	

	public function beforeAction(Event $event){
		$this->ControllerAction->field('module', ['visible' => false]);
		$this->ControllerAction->field('field', ['visible' => false]);
		$this->ControllerAction->field('visible', ['visible' => false]);
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
}
