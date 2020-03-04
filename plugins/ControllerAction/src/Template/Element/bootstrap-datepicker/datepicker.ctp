<?php
$this->Html->scriptStart(['block' => 'scriptBottom']);
?>
$(function () {
<?php
$datepickerScript = "var datepicker%s = $('#%s').datepicker(%s);\n";
if (isset($datepicker)) {
	foreach ($datepicker as $key => $obj) {
                $obj['date_options']['language'] = $dateLanguage;
		echo sprintf($datepickerScript, $key, $obj['id'], json_encode($obj['date_options']));
	}

	echo "$( document ).on('DOMMouseScroll mousewheel scroll', function(){\n";
		echo "window.clearTimeout( t );\n";
	        echo "t = window.setTimeout( function(){\n";
				foreach ($datepicker as $key => $obj) {
					echo sprintf("datepicker%s.datepicker('place');\n", $key);
				}
	        echo "});\n";
	    echo "}\n";
	echo ");\n";
}
/*
foreach ($datepicker as $key => $obj) {
	$_setting = array();
	$eventKeys = array('changeDate', 'show', 'hide', 'onRender');
	$events = array();
	$id = $obj['id'];
	unset($obj['id']);
	if (!empty($obj['startDate'])) {
		$_setting['startDate'] = date('d-m-Y', strtotime($obj['startDate']));
		unset($obj['startDate']);
	}

	if (!empty($obj['endDate'])) {
		$_setting['endDate'] = date('d-m-Y', strtotime($obj['endDate']));
		unset($obj['endDate']);
	}
	
	foreach ($eventKeys as $i) {
		if (isset($obj[$i])) {
			$events[$i] = $obj[$i];
			unset($obj[$i]);
		}
	}
	
	$_setting = array_merge($_setting, $obj);
	
	if (empty($obj['disabled']) || (isset($obj['disabled']) && $obj['disabled'] != 'disabled')) {
		echo "$('#" . $id . "').datepicker(";
		if (!empty($_setting)) {
			echo json_encode($_setting);
		}
		echo ")";
		foreach ($events as $i => $function) {
			echo ".on('$i', $function)";
		}
		echo ";\n";
	}
}*/
?>
});
<?php
$this->Html->scriptEnd();
?>
