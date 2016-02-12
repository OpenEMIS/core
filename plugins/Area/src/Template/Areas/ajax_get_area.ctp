<?php
	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);

	$url = $this->Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
	$formClass = 'form-control';
	if ($formError) {
		$formClass .= ' form-error';
	}

	$count = 0;
	foreach ($path as $obj) {
		$name = $obj->$levelAssociation->name;
		if (!($tableName=='Area.AreaAdministratives' && $count==0)) {
			echo $this->Form->input($name, [
				'class' => $formClass,
				'div' => false,
				'data-source' => $tableName,
				'target-model' => $targetModel,
				'label' => __($name),
				'url' => $url,
				'onchange' => 'Area.reload(this)',
				'options' => $obj->list,
				'disabled' => false,
				'default' => $obj->selectedId,
				'form-error' => $formError,
				'display-country' => $displayCountry
			]);
		}
		$count++;
	}
?>
