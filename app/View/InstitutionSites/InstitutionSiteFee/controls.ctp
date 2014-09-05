<div class="row page-controls">
	<?php
	echo $this->Form->input('school_year_id', array(
		'class' => 'form-control',
		'label' => false,
		'options' => $yearOptions,
		'default' => $selectedYear,
		'div' => 'col-md-3',
		'url' => sprintf('%s/%s/index', $this->params['controller'], $model),
		'onchange' => 'jsForm.change(this)'
	));
	?>
</div>
