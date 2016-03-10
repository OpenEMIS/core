<?php
	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);

	$url = $this->Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
	$formClass = 'form-control';
	if ($formError) {
		$formClass .= ' form-error';
	}

	$count = 0;

	if (!empty($path)) {
		foreach ($path as $obj) {
			$name = $obj->$levelAssociation->name;
			if (!($tableName=='Area.AreaAdministratives' && $count==0)) {
				$options = [
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
					'display-country' => $displayCountry,
				];

				if (isset($obj['readonly'])) {
					$options['readonly'] = $obj['readonly'];
				}
				echo $this->Form->input($name, $options);
			}
			$count++;
		}
	}
?>
