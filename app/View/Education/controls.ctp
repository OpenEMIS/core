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
	
	<?php if(isset($systems)) { ?>
		<div class="col-md-4">
			<?php
				echo $this->Form->input('education_system_id', array(
					'id' => 'EducationSystemId',
					'class'=>'form-control',
					'label' => false,
					'div' => false,
					'options' => $systems,
					'default' => $selectedSystem,
					'url' => 'Education/index',
					'onchange' => 'jsForm.change(this)'
				));
			?>
		</div>
	<?php } ?>
	
	<?php if(!empty($levels)) { ?>
		<div class="col-md-4">
			<?php
				echo $this->Form->input('education_level_id', array(
					'id' => 'EducationLevelId',
					'class'=>'form-control',
					'label' => false,
					'div' => false,
					'options' => $levels,
					'onchange' => 'education.switchLevel(this)'
				));
			?>
		</div>
	<?php } ?>
</div>