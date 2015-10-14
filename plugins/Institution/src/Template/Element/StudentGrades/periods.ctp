<div class="clearfix form-horizontal">
	<?= 
		$this->Form->input($alias.".academic_period", [
			'label' => $this->Label->get('StudentPromotion.current_period'),
			'type' => 'string',
			'value' => $period,
			'disabled' => 'disabled'
		]);
	?>
	<?= 
		$this->Form->input($alias.".next_academic_period_id", [
			'label' => $this->Label->get('StudentPromotion.next_period'),
			'type' => 'select',
			'options' => $periods,
			'onchange' => "$('#reload').click();return false;"
		]);
	?>
</div>
