<?php if($obj) { ?>

<div class="table_row" row-id="<?php echo $obj['id']; ?>">
	<?php
	$year = date('Y', strtotime($obj['start_date']));
	$model = 'InstitutionSiteStudent.' . $i;
	echo $this->Form->hidden($model.'.id', array('value' => $obj['id']));
	echo $this->Form->hidden($model.'.start_date.year', array('value' => $year));
	?>
	<div class="table_cell cell_id_no"><?php echo $idNo; ?></div>
	<div class="table_cell"><?php echo $name; ?></div>
	<div class="table_cell cell_start_date center">
		<?php
		echo $this->Form->input($model.'.start_date', array(
			'label' => false,
			'div' => false,
			'type' => 'date',
			'class' => 'select',
			'dateFormat' => 'DM',
			'selected' => $obj['start_date']
		));
		?>
	</div>
	<div class="table_cell cell_datepicker center">
		<?php
		echo $this->Form->input($model.'.end_date', array(
			'label' => false,
			'div' => false,
			'type' => 'date',
			'class' => 'select',
			'dateFormat' => 'DMY',
			'selected' => $obj['end_date'],
			'minYear' => $year,
			'maxYear' => $year + 10,
			'orderYear' => 'asc'
		));
		?>
	</div>
	<div class="table_cell cell_icon_action">
		<span class="icon_delete" onclick="InstitutionSiteProgrammes.removeStudentFromList(this)"></span>
	</div>
</div>

<?php } ?>