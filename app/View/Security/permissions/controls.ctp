<div class="row page-controls">
	<div class="col-md-3">
		<?php
		echo $this->Form->input('security_role_id', array(
			'div' => false,
			'label' => false,
			'class' => 'form-control',
			'options' => $roleOptions,
			'default' => $selectedRole,
			'onchange' => 'jsForm.change(this)',
			'url' => $this->params['controller'] . '/permissions'
		));
		?>
	</div>
	
	<div class="col-md-3">
		<?php
		echo $this->Form->input('module', array(
			'div' => false,
			'label' => false,
			'class' => 'form-control',
			'options' => $moduleOptions,
			'default' => $selectedModule,
			'onchange' => 'jsForm.change(this, false)',
			'url' => $this->params['controller'] . '/permissions/' . $selectedRole . '/'
		));
		?>
	</div>
</div>
