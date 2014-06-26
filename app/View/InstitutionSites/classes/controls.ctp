<div class="row page-controls">
	<div class="col-md-4">
		<?php
		echo $this->Form->input('options', array(
			'class' => 'form-control',
			'label' => false,
			'div' => false,
			'options' => $actionOptions,
			'default' => $selectedAction,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'],
			'autocomplete' => 'off'
		));
		?>
	</div>
	
	<?php if(isset($studentActionOptions) && !empty($studentActionOptions)) : ?>
		<div class="col-md-6">
			<?php
				echo $this->Form->input('education_grade_id', array(
					'class' => 'form-control',
					'label' => false,
					'div' => false,
					'options' => $studentActionOptions,
					'default' => $selectedGrade,
					'onchange' => 'jsForm.change(this)',
					'url' => $this->params['controller'] . '/' . $selectedAction,
					'autocomplete' => 'off'
				));
			?>
		</div>
	<?php endif; ?>
</div>
