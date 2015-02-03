<div class="row page-controls">
	<div class="col-md-4">
		<?php
		echo $this->Form->input('options', array(
			'class' => 'form-control',
			'label' => false,
			'div' => false,
			'options' => $academicPeriodOptions,
			'default' => $selectedAction,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'],
			'autocomplete' => 'off'
		));
		?>
	</div>
</div>