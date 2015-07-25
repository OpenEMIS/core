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
			$targetTable = TableRegistry::get($targetModel);

			$areaOptions = $targetTable
				->find('list')
				->toArray();

			$fieldName = $attr['model'] . '.' . $attr['field'];
			$options['onchange'] = "Area.reload(this)";
			$options['url'] = $Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
			$options['data-source'] = $attr['source_model'];
			$options['target-model'] = $attr['model'];
			$options['field-name']=$fieldName;
			$options['options'] = $areaOptions;
			$options['id'] = 'areapicker';
			$options['area-label'] = $options['label'];

			$value = "<div class='areapicker'>";
			$value .= $Form->input($fieldName, $options);
			$value .= "</div>";
			$value .= $Form->hidden($fieldName);
		}
		return $value;
	}
}
