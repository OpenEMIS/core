<?php 
namespace Area\Model\Behavior;

use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class AreapickerBehavior extends Behavior {
	protected $_defaultConfig = [
		'display_country' => true
	];

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
			$targetTable = TableRegistry::get($targetModel);
			$condition = [];
			if (!$this->config('display_country')) {
				if ($targetModel == 'Area.AreaAdministratives') {
					$condition = [$targetTable->aliasField('is_main_country') => 1];
				}
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
			$option['display-country'] = 1;
			if (!$this->config('display_country')) {
				$options['display-country'] = 0;
			}

			$value = "<div class='areapicker'>";
			$value .= $Form->input($fieldName, $options);
			$value .= "</div>";
			$value .= $Form->hidden($fieldName);
			$value .= $Form->error($fieldName);
		}
		return $value;
	}
}
