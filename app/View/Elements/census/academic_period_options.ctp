<div class="row page-controls">
	<div class="col-md-3">
		<?php
		$url = 'Census/' . $this->action;
		
		if (isset($model) && isset($action)) {
			$url = "Census/$model/" . $action;
		}
		echo $this->Form->input('academic_period_id', array(
			'label' => false,
			'div' => false,
			'options' => $academicPeriodList,
			'class' => 'form-control',
			'default' => $selectedAcademicPeriod,
			'onchange' => 'jsForm.change(this)',
			'url' => $url
		));
		?>
	</div>
	<div class="col-md-9">
		<?php echo $this->element('census/legend'); ?>
	</div>
</div>
