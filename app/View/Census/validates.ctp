<div class="field_row"><?php echo $msg; ?></div>
<div class="field_row field_input" style="margin-top: 20px">
	<div class="field_name" style="width: 150px;"><?php echo $label; ?></div>
	<div class="field_value">
		<?php
		echo $this->Form->input('school_year_id', array(
			'id' => 'SchoolYearId',
			'label' => false,
			'div' => false,
			'options' => $yearOptions
		));
		?>
	</div>
</div>
