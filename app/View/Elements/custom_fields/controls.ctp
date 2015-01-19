<div class="row page-controls">
	<?php
		echo $this->Form->input($Custom_Module.'_id', array(
			'class' => 'form-control',
			'label' => false,
			'options' => $moduleOptions,
			'default' => 'module:' . $selectedModule,
			'div' => 'col-md-3',
			'url' => $this->params['controller'] . '/' . $this->request->action,
			'onchange' => 'jsForm.change(this)'
		));

		if(isset($parentOptions)) {
			echo $this->Form->input($Custom_Parent.'_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $parentOptions,
				'default' => 'parent:' . $selectedParent,
				'div' => 'col-md-3',
				'url' => $this->params['controller'] . '/' . $this->request->action . '/module:' . $selectedModule,
				'onchange' => 'jsForm.change(this)'
			));
		}
	?>
</div>