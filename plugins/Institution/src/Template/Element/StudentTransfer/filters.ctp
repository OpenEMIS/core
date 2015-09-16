<div class="clearfix form-horizontal">
	<?=
		$this->Form->input($alias.".next_academic_period_id", [
			'label' => $this->Label->get('StudentTransfer.next_period'),
			'type' => 'select',
			'options' => $nextPeriodOptions,
			'onchange' => "$('#reload').click();return false;"
		]);
	?>
	<?=
		$this->Form->input($alias.".next_education_grade_id", [
			'label' => $this->Label->get('StudentTransfer.next_grade'),
			'type' => 'select',
			'options' => $nextGradeOptions,
			'onchange' => "$('#reload').click();return false;"
		]);
	?>
	<?=
		$this->Form->input($alias.".next_institution_id", [
			'label' => $this->Label->get('StudentTransfer.next_institution_id'),
			'type' => 'select',
			'options' => $institutionOptions
		]);
	?>
	<?=
		$this->Form->input($alias.".student_transfer_reason_id", [
			'label' => $this->Label->get('StudentTransfer.student_transfer_reason_id'),
			'type' => 'select',
			'options' => $reasonOptions
		]);
	?>
</div>
