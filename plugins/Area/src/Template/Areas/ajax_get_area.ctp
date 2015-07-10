<?php
	
	$inputName = "value";

	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);

	// Using the url helper to build the url
	$url = $this->Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
	$selectAreaOption = ["-1"=>"--Select Area--"];
	// From selected to root
	foreach($path as $obj){
		echo $this->Form->input($inputName, array(
			'class' => 'form-control',
			'div' => false,
			'data-source' => $tableName,
			'label' => $obj->level->name,
			'url' => $url,
			'onchange' => 'Area.reload(this)',
			'options' => $obj->list,
			// 'disabled' => $disabled,
			'default' => $obj->id
		));
	}

	// $disabled = false;
	// // if(empty($children)){
	// // 	$disabled = true;
	// // }
	// foreach($children as $obj){
	// 	// For children of selected
	// 	echo $this->Form->input($inputName, array(
	// 		'class' => 'form-control',
	// 		'div' => false,
	// 		'data-source' => $tableName,
	// 		'label' => $children->level->name,
	// 		'url' => $url,
	// 		'onchange' => 'Area.reload(this)',
	// 		'options' => ($children->list),
	// 		'disabled' => $disabled,
	// 		'default' => $children->id
	// 	));
	// }
?>
