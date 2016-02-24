<?php 
namespace Area\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class AreapickerBehavior extends Behavior {
	public function implementedEvents() {
        $events = parent::implementedEvents();

		$events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
		$events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';

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
			if ($targetModel == 'Area.AreaAdministratives') {
				$subQueryForWorldRecord = $targetTable->find()->select([$targetTable->aliasField('id')])->where([$targetTable->aliasField('parent_id') => -1]);
				$areaOptions = $targetTable
					->find('list')
					->where([$targetTable->aliasField('parent_id').' <> ' => -1])
					->order([$targetTable->aliasField('parent_id'), $targetTable->aliasField('order')])
					->toArray();
			}	
			if ($targetModel == 'Area.Areas' && isset($attr['displayCountry'])) {
				$options['display-country'] = $entity->area_id;
			} else if (isset($attr['displayCountry']) && !$attr['displayCountry']) {
				$options['display-country'] = 0;
				if ($this->_table->action == 'add') {
					$areaOptions = $targetTable
						->find('list')
						->where([$targetTable->aliasField('is_main_country') => 1])
						->order([$targetTable->aliasField('order')])
						->toArray();
				}
			}
			
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
						'attr' => ['label' => __($area['level'])],
						'value' => $area['area_name'],
						'after' => $after
					]);
					$after = $field.$key;
				}
			}
		}
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$userId = $this->_table->Auth->user('id');
		$areasByUser = $this->_table->AccessControl->getAreasByUser($userId);
		if (!$this->_table->AccessControl->isAdmin() && empty($areasByUser)) {
			foreach ($this->_table->fields as $field => $attr) {
				if ($attr['type'] == 'areapicker' && $attr['source_model'] == 'Area.Areas') {
					$this->_table->fields[$field]['type'] = 'hidden';
					$targetModel = $attr['source_model'];
					$areaId = $entity->$field;
					$list = $this->getAreaLevelName($targetModel, $areaId);
					$after = $field;
					foreach ($list as $key => $area) {
						$this->_table->ControllerAction->field($field.$key, [
							'type' => 'readonly', 
							'attr' => ['label' => __($area['level']), 'value' => $area['area_name']],
							'value' => $area['area_name'],
							'after' => $after
						]);
						$after = $field.$key;
					}
				}
			}
		}	
	}

	public function getAreaLevelName($targetModel, $areaId) {
		$targetTable = TableRegistry::get($targetModel);
		$levelAssociation = Inflector::singularize($targetTable->alias()).'Levels';
		$path = $targetTable
			->find('path', ['for' => $areaId])
			->contain([$levelAssociation])
			->select(['level' => $levelAssociation.'.name', 'area_name' => $targetTable->aliasField('name')])
			->bufferResults(false)
			->toArray();

		if ($targetModel == 'Area.AreaAdministratives') {
			// unset world
			unset($path[0]);
		}
		return $path;
	
	}
}
