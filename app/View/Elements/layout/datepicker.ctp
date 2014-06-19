<?php
$this->Html->scriptStart(array('inline' => false, 'block' => 'scriptBottom'));
?>
$(function () {
<?php
foreach ($datepicker as $key => $obj) {//pr($obj);
	$_setting = array();
	if (!empty($obj['startDate'])) {
		$_setting['startDate'] = date('d-m-Y', strtotime($obj['startDate']));
	}

	if (!empty($obj['endDate'])) {
		$_setting['endDate'] = date('d-m-Y', strtotime($obj['endDate']));
	}
	
	if (empty($obj['disabled']) || (isset($obj['disabled']) && $obj['disabled'] != 'disabled')) {
		echo "$('#" . $obj['id'] . "').datepicker(";
		if (!empty($_setting)) {
			echo json_encode($_setting);
		}
		echo ");\n";
	}
}
?>
});
<?php
$this->Html->scriptEnd();
?>
