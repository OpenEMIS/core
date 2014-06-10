<?php
$this->Html->scriptStart(array('inline' => false, 'block' => 'scriptBottom'));
?>
$(function () {
	<?php 
	foreach($datepicker as $key => $obj) {
		$_setting = array();
		if(!empty($obj['startDate'])){
			$_setting['startDate'] = date('d-m-Y', strtotime($obj['startDate']));
		}
		
		if(!empty($obj['endDate'])){
			$_setting['endDate'] = date('d-m-Y', strtotime($obj['endDate']));
		}
		echo "$('#" . $obj['id'] . "').datepicker(";
		if(!empty($_setting)){
			echo json_encode($_setting);
		}
		echo ");\n";
	}
	?>
});
<?php
$this->Html->scriptEnd();
?>
