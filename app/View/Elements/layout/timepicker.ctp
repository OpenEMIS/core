<?php
$this->Html->scriptStart(array('inline' => false, 'block' => 'scriptBottom'));
?>
$(function () {
	<?php
	foreach($timepicker as $id) {
		echo "$('#" . $id . "').timepicker();\n";
	}
	?>
});
<?php
$this->Html->scriptEnd();
?>
