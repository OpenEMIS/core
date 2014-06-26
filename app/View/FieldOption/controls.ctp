<div class="row page-controls">
	<?php $this->Form->create('FieldOption', array('inputDefaults' => array('label'=>false, 'div'=>false, 'class'=>'default', 'autocomplete'=>'off'))); ?>
	<div class="col-md-3">
		<?php
		echo $this->Form->input('options', array(
			'class' => 'form-control',
			'options' => $options,
			'default' => $selectedOption,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/index'
		));
		?>
	</div>
	
	<?php if(isset($subOptions)) : ?>
	<div class="col-md-3">
		<?php
		echo $this->Form->input('suboptions', array(
			'class' => 'form-control',
			'options' => $subOptions,
			'default' => $selectedSubOption,
			'onchange' => 'jsForm.change(this, false)',
			'url' => $this->params['controller'] . '/index/' . $selectedOption . '/' . $conditionId . ':'
		));
		?>
	</div>
	<?php endif; ?>
	<?php $this->Form->end(); ?>
</div>