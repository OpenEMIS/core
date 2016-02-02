<?php 
namespace Area\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class AreapickerBehavior extends Behavior {

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

			if ($targetModel == 'Area.Areas' && isset($attr['displayCountry'])) {
				$options['display-country'] = $entity->area_id;
			} else if (isset($attr['displayCountry']) && !$attr['displayCountry']) {
				$condition = [$targetTable->aliasField('is_main_country') => 1];
				$options['display-country'] = 0;
			}

			$areaOptions = $targetTable
				->find('list')
				->where($condition)
				->toArray();
				;
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

	public function onGetReadOnlyAreasElement(Event $event, $action, Entity $entity, $attr, $options) {
		$targetModel = $attr['source_model'];
		$entityKey = $attr['field'];
		$areaId = $entity->$entityKey;
		switch ($action) {
			// case 'view':
			case 'edit':
				$fieldName = $attr['model'] . '.' . $attr['field'];
				$attr['label'] = $options['label'];
				$attr['key'] = $entityKey;
				$attr['id'] = $areaId;
				$list = $this->getAreaLevelName($targetModel, $areaId);
				$attr['list'] = $list;
				return $event->subject()->renderElement('Area.read_only_areas', ['attr' => $attr]);
				break;
		}
	}

	public function getAreaLevelName($targetModel, $areaId) {
		$targetTable = TableRegistry::get($targetModel);
		$path = $targetTable
			->find('path', ['for' => $areaId])
			->contain(['Levels'])
			->select(['level' => 'Levels.name', 'area_name' => $targetTable->aliasField('name')])
			->hydrate(false)
			->toArray();
		return $path;
	}
}
