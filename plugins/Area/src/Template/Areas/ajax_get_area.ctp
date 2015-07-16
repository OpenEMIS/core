<?php
	$inputName = $targetModel;
	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);

	$url = $this->Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);

	$count = 0;
	foreach ($path as $obj) {
		$name = $inputName;
		if ($count == 0) {
			$name .= '.' . $tableName;
		} else {
			$name = $obj->level->name;
		}

		if (!($tableName=='Area.AreaAdministratives' && $count==0)) {
			echo $this->Form->input($name, array(
				'class' => 'form-control',
				'div' => false,
				'data-source' => $tableName,
				'target-model' => $targetModel,
				'label' => $tableName ." ". $obj->level->name,
				'url' => $url,
				'onchange' => 'Area.reload(this)',
				'options' => $obj->list,
				'disabled' => false,
				'default' => $obj->selectedId
			));
		}
		$count++;
	}
?>
