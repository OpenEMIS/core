<?php
	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);

	$url = $this->Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);

	$count = 0;
	foreach ($path as $obj) {
		
		$name = $obj->level->name;
		if (!($tableName=='Area.AreaAdministratives' && $count==0)) {
			echo $this->Form->input($fieldName.".".$name, [
				'class' => 'form-control',
				'div' => false,
				'data-source' => $tableName,
				'target-model' => $targetModel,
				'field-name' => $fieldName,
				'label' => $fieldNameForFormat . " - " . $name,
				'url' => $url,
				'onchange' => 'Area.reload(this)',
				'options' => $obj->list,
				'disabled' => false,
				'default' => $obj->selectedId
			]);
		}
		$count++;
	}
?>
