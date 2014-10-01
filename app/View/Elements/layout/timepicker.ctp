<?php
$this->Html->scriptStart(array('inline' => false, 'block' => 'scriptBottom'));
?>
$(function () {
<?php
foreach ($timepicker as $key => $obj) {
	$_setting = array();
	$eventKeys = array('show', 'hide', 'update');
	$events = array();
	$id = $obj['id'];
	unset($obj['id']);
	
	foreach ($eventKeys as $i) {
		if (isset($obj[$i])) {
			$events[$i] = $obj[$i];
			unset($obj[$i]);
		}
	}
	
	$_setting = array_merge($_setting, $obj);
	
	if (empty($obj['disabled']) || (isset($obj['disabled']) && $obj['disabled'] != 'disabled')) {
		echo "$('#" . $id . "').timepicker(";
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
