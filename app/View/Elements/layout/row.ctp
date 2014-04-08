<?php
if(!isset($rowClass)) {
	$rowClass = 'row';
} else {
	$rowClass = 'row ' . $rowClass;
}
?>

<div class="<?php echo $rowClass; ?>"><?php echo $rowBody; ?></div>
