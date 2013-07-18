<?php

$arrMap = array(
	''=>'Areas',
	'levels'=>'Areas Levels',
	'AreaEducation'=>'Areas (Education)',
	'AreaEducationLevels'=>'Area Levels (Education)'
);
?>

<div style="margin-bottom: 10px;">
	
	<?php
	
	echo  $this->Form->input('area_cat', array(
		'label' => false,
		'div' => false,
		'options' => $arrMap,
		'default' => rtrim($this->params['action'],'Edit'),
		'url' => $this->params['controller'],
		'onchange' => 'jsForm.change(this)'
	));
	?>
	
	</select>
</div>