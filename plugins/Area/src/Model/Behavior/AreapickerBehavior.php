<?php 
namespace Area\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class AreapickerBehavior extends Behavior {
	public function implementedEvents() {
        $events = parent::implementedEvents();

		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.edit.afterQuery'] = 'editAfterQuery';
		$events['ControllerAction.Model.edit.beforePatch'] = 'editBeforePatch';

        return $events;
    }

	public function onGetAreapickerElement(Event $event, $action, Entity $entity, $attr, $options) {
		$value = $entity->$attr['field'];
		if ($action == 'edit') {
			$includes = [
				'area' => ['include' => true, 'js' => 'Area.area']
			];

			$HtmlField = $event->subject();
			$HtmlField->includes = array_merge($HtmlField->includes, $includes);
			$Url = $HtmlField->Url;
			$Form = $HtmlField->Form;
			$targetModel = $attr['source_model'];
			$options['display-country'] = 1;
			$targetTable = TableRegistry::get($targetModel);
			$condition = [];
			$areaOptions = $targetTable
				->find('list');

			// Pick the first found parent of area administrative
			if ($targetModel == 'Area.AreaAdministratives' && (!isset($attr['displayCountry']) || (isset($attr['displayCountry']) && $attr['displayCountry']))) {
				$areaOptions = $areaOptions
					->where([$targetTable->aliasField('parent_id').' <> ' => -1])
					->order([$targetTable->aliasField('lft')]);
			}	

			if ($targetModel == 'Area.Areas' && isset($attr['displayCountry'])) {
				if (!$entity->isNew()) {
					$options['display-country'] = $entity->area_id;
				} else {
					$options['display-country'] = 0;
				}

				// Filter the initial area list to show only the authorised area
				$authorisedArea = $this->_table->AccessControl->getAreasByUser();
				$areaCondition = [];
				foreach ($authorisedArea as $area) {
					$areaCondition[] = [
						$targetTable->aliasField('lft').' >= ' => $area['lft'],
						$targetTable->aliasField('rght').' <= ' => $area['rght']
					];
				}
				if (!empty($authorisedArea)) {
					$areaOptions = $areaOptions
						->where(['OR' => $areaCondition]);
				}
			} 
			// If there is a restriction on the area administrative's main country to display (Use in Institution only)
			else if ($targetModel == 'Area.AreaAdministratives' && isset($attr['displayCountry']) && !$attr['displayCountry']) {
				$options['display-country'] = 0;
				if ($this->_table->action == 'add') {
					$areaOptions = $areaOptions
						->where([$targetTable->aliasField('is_main_country') => 1])
						->order([$targetTable->aliasField('order')]);
				}
			}

			$areaOptions = $areaOptions->toArray();
			
			$fieldName = $attr['model'] . '.' . $attr['field'];
			$options['onchange'] = "Area.reload(this)";
			$options['url'] = $Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
			$options['data-source'] = $attr['source_model'];
			$options['target-model'] = $attr['model'];
			$options['field-name'] = $fieldName;
			$options['options'] = $areaOptions;
			$options['id'] = 'areapicker';
			$options['area-label'] = $options['label'];
			$arr = $entity->errors($attr['field']);
			if (!empty($arr)) {
				$options['form-error'] = true;
			} else {
				$options['form-error'] = false;
			}

			$value = "<div class='areapicker'>";
			$value .= $Form->input($fieldName, $options);
			$value .= "</div>";
			$value .= $Form->hidden($fieldName);
			$value .= $Form->error($fieldName);
		}
		return $value;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		foreach ($this->_table->fields as $field => $attr) {
			if ($attr['type'] == 'areapicker') {
				$this->_table->fields[$field]['type'] = 'hidden';
				$targetModel = $attr['source_model'];
				$areaId = $entity->$field;
				if (!empty($areaId)) {
					$list = $this->getAreaLevelName($targetModel, $areaId);
				} else {
					$list = [];
				}
				$after = $field;
				foreach ($list as $key => $area) {
					$this->_table->ControllerAction->field($field.$key, [
						'type' => 'readonly', 
						'attr' => ['label' => __($area['level_name'])],
						'value' => $area['area_name'],
						'after' => $after
					]);
					$after = $field.$key;
				}
			}
		}
	}

	public function editAfterQuery(Event $event, Entity $entity) {
		$userId = $this->_table->Auth->user('id');
		$areasByUser = $this->_table->AccessControl->getAreasByUser($userId);

		// $areasByUser will always be empty for system groups because system groups are linked directly to schools
		if (!$this->_table->AccessControl->isAdmin() && empty($areasByUser)) {
			foreach ($this->_table->fields as $field => $attr) {
				if ($attr['type'] == 'areapicker' && $attr['source_model'] == 'Area.Areas') {
					$this->_table->fields[$field]['visible'] = false;
					$targetModel = $attr['source_model'];
					$areaId = $entity->$field;
					$list = $this->getAreaLevelName($targetModel, $areaId);
					$after = $field;
					foreach ($list as $key => $area) {
						$this->_table->ControllerAction->field($field.$key, [
							'type' => 'disabled', 
							'attr' => ['label' => __($area['level_name']), 'value' => $area['area_name']],
							'value' => $area['area_name'],
							'after' => $after
						]);
						$after = $field.$key;
					}
				}
			}
			$entity->area_restricted = true;
		}
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// to prevent html injection on area_id
		if ($entity->has('area_restricted') && $entity->area_restricted == true) {
			if (array_key_exists('Institutions', $data)) {
				$data['Institutions']['area_id'] = $entity->area_id;
				$data['Institutions']['isSystemGroup'] = true; // this flag is to be used in ValidationBehavior->checkAuthorisedArea
			}
		}
	}

	public function getAreaLevelName($targetModel, $areaId) {
		$targetTable = TableRegistry::get($targetModel);
		$levelAssociation = Inflector::singularize($targetTable->alias()).'Levels';
		$path = $targetTable
			->find('path', ['for' => $areaId])
			->contain([$levelAssociation])
			->select(['level_name' => $levelAssociation.'.name', 'area_name' => $targetTable->aliasField('name')])
			->bufferResults(false)
			->toArray();

		if ($targetModel == 'Area.AreaAdministratives') {
			// unset world
			unset($path[0]);
		}
		return $path;
	
	}
}
