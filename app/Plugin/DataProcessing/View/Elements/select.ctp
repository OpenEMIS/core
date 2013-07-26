<?php
$arrMap = array(
	'genReports'=>'Reports',
	'genIndicators'=>'Indicator',
	'genEstimates'=>'Estimates'
);
?>
<div style="margin-bottom: 10px;">
	<?php
	
	echo  $this->Form->input('area_cat', array(
		'label' => false,
		'div' => false,
		'options' => $arrMap,
		'default' => rtrim($this->params['action']),
		'url' => $this->params['controller'],
		'onchange' => "location.href=$(this).val();"
	));
	?>
</div>