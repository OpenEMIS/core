<?php if(!empty($pagination)) { ?>
<ul id="pagination" class="none" url="InstitutionSites/studentsListAjax">
	<?php foreach($pagination as $item) { ?>
		<?php if($first === $item) { ?>
				<li class="current"><?php echo $item; ?></li>
		<?php } else { ?>
				<li><a href="javascript: void(0)"><?php echo $item; ?></a></li>
		<?php } ?>
	<?php } ?>
</ul>
<?php } ?>

<?php $model = 'InstitutionSiteProgrammeStudent.%d.%s'; ?>
<?php foreach($data as $i => $obj) { ?>
<?php 
	$fullname = $obj['first_name'] . ' ' . $obj['last_name'];
	if(strlen($name) > 1 && $name === $fullname) {
		$fullname = $this->Utility->highlight($name, $fullname);
	}
?>
<div class="table_row" student-id='<?php echo $obj['id']; ?>'>
	<?php
	if($edit) {
		echo $this->Form->hidden(sprintf($model, $i, 'id'), array('value' => $obj['id'], 'label' => false, 'div' => false));
		echo $this->Form->hidden(sprintf($model, $i, '.start_date.year'), array('value' => $obj['year'], 'label' => false, 'div' => false));
	}
	?>
	<div class="table_cell"><?php echo $obj['identification_no']; ?></div>
	<div class="table_cell "><?php echo $fullname; ?></div>
	<div class="table_cell center">
		<?php
		if($edit) {
			echo $this->Form->input(sprintf($model, $i, 'start_date'), array(
				'label' => false,
				'div' => false,
				'type' => 'date',
				'class' => 'select',
				'dateFormat' => 'DM',
				'selected' => $obj['start_date']
			));
		} else {
			echo $obj['start_date'];
		}
		?>
	</div>
	<div class="table_cell center">
		<?php
		if($edit) {
			echo $this->Form->input(sprintf($model, $i, 'end_date'), array(
				'label' => false,
				'div' => false,
				'type' => 'date',
				'class' => 'select',
				'dateFormat' => 'DMY',
				'selected' => $obj['end_date'],
				'minYear' => $obj['year'],
				'maxYear' => $obj['year'] + 10,
				'orderYear' => 'asc'
			));
		} else {
			echo $obj['end_date'];
		}
		?>
	</div>
	<?php if($edit) { ?>
	<div class="table_cell"><span class="icon_delete" onclick="InstitutionSiteStudents.removeStudentFromList(this)"></span></div>
	<?php } ?>
</div>
<?php } ?>