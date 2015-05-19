<?php
$this->Html->scriptStart(array('inline' => false, 'block' => 'scriptBottom'));
?>
$(function () {
<?php
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
}
?>
});
<?php
$this->Html->scriptEnd();
?>
