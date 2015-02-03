<div class="field_row"><?php echo $msg; ?></div>
<div class="field_row field_input" style="margin-top: 20px">
	<div class="field_name" style="width: 150px;"><?php echo $label; ?></div>
	<div class="field_value">
		<?php
		echo $this->Form->input('academic_period_id', array(
			'id' => 'AcademicPeriodId',
			'label' => false,
			'div' => false,
			'options' => $academicPeriodOptions
		));
		?>
	</div>
</div>
