<div class="clearfix form-horizontal">
	<?= 
		$this->Form->input($alias.".academic_period", [
			'label' => $this->Label->get('InstitutionGradeStudents.current_period'),
			'type' => 'string',
			'value' => $period,
			'disabled' => 'disabled'
		]);
	?>
	<?= 
		$this->Form->input($alias.".next_academic_period_id", [
			'label' => $this->Label->get('InstitutionGradeStudents.next_period'),
			'type' => 'select',
			'options' => $periods
		]);
	?>
</div>
