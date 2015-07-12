<?php
	
	$inputName = "value";

	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);

	// Using the url helper to build the url
	$url = $this->Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
	// From selected to root
	$count = 0;
	foreach($path as $obj){
		if(! ($tableName=='Area.AreaAdministratives' && $count==0)){
			echo $this->Form->input($inputName, array(
				'class' => 'form-control',
				'div' => false,
				'data-source' => $tableName,
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
