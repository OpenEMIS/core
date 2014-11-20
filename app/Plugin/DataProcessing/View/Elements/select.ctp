<?php
$arrMap = array(
	'genEstimates'=>'Aggregated Totals',
	'genReports'=>'Reports',
	'genIndicators'=>'Standard Indicators',
	'genCustoms'=>'Custom Indicators'
);
?>
<div class="row page-controls">
<div class="col-md-4">
	<?php
	echo  $this->Form->input('area_cat', array(
		'label' => false,
		'div' => false,
		'options' => $arrMap,
		'class'=>'form-control',
		'default' => rtrim($this->params['action']),
		'url' => $this->params['controller'],
		'onchange' => "location.href=$(this).val();"
	));
	?>
</div>
</div>
