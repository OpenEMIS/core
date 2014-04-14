<?php
$this->Html->scriptStart(array('inline' => false, 'block' => 'scriptBottom'));
?>
$(function () {
	<?php
	foreach($datepicker as $id) {
		echo "$('#" . $id . "').datepicker();\n";
	}
	?>
});
<?php
$this->Html->scriptEnd();
?>
