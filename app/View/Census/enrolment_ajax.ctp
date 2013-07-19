<?php
$total = 0;
if(!empty($enrolment) && !(sizeof($enrolment)==1 && $enrolment[0]['male']==0 && $enrolment[0]['female']==0)) {
?>
<div class="table_body">
	<?php 
	$count = 0;
	foreach($enrolment as $record) {
		$count++;
		$total += $record['male'] + $record['female'];
		$record_tag="";
		foreach ($source_type as $k => $v) {
			if ($record['source']==$v) {
				$record_tag = "row_" . $k;
			}
		}

	?>
	<div class="table_row <?php echo $count%2==0 ? 'even' : ''; ?>">
		<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['age']; ?></div>
		<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['male']; ?></div>
		<div class="table_cell cell_number <?php echo $record_tag; ?>"><?php echo $record['female']; ?></div>
		<div class="table_cell cell_total cell_number <?php echo $record_tag; ?>"><?php echo $record['male'] + $record['female']; ?></div>
	</div>
	<?php } ?>
</div>
<?php } ?>

<div class="table_foot">
	<div class="table_cell"></div>
	<div class="table_cell"></div>
	<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
	<div class="table_cell cell_value cell_number"><?php echo $total ?></div>
</div>