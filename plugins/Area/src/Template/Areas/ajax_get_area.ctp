<?php
	// Using the url helper to build the url
	$url = $this->Url->build(['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'ajaxGetArea']);
	echo $this->Form->input("value", array(
		'class' => 'form-control',
		'div' => false,
		'name' => $tableName,
		'label' => false,
		'url' => $url,
		'onchange' => 'Area.reload(this)',
		'options' => $list,
		// 'selected' => $value
	));
?>
