<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('setup_variables', false);
echo $this->Html->script('credit_hour', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training_credit_hours" class="content_wrapper edit training_credit_hours">
	<?php
	echo $this->Form->create('SetupVariables', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Setup', 'action' => 'setupVariablesEdit')
	));
	?>
	<h1>
		<span><?php echo __($header); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'setupVariables', $selectedCategory), array('class' => 'divider')); ?>
	</h1>
	
	<div class="row category">
		<?php
		echo $this->Form->input('category', array(
			'id' => 'category',
			'options' => $categoryList,
			'default' => $selectedCategory,
			'url' => 'Setup/setupVariables/',
			'onchange' => 'setup.changeCategory()'
		));
		?>
	</div>
	
	<?php
	$index = 0;
	foreach($category as $type) { ?>
		<?php foreach($type['items'] as $typeName => $options) { ?>
		<fieldset class="section_group">
			<legend><?php echo $typeName ?></legend>
			<?php			
			echo $this->Form->hidden('model', array('id' => 'model', 'value' => $options['model']));
			
			if(isset($options['conditions'])) {
				foreach($options['conditions'] as $conditionName => $conditionValue) {
					echo $this->Form->hidden('condition', array('conditionName' => $conditionName, 'value' => $conditionValue));
				}
			}
			?>
			<div class="table">
				<div class="table_head">
					<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
					<div class="table_cell"><?php echo __('Option'); ?></div>
					<div class="table_cell cell cell_national_code"><?php echo __('Min'); ?></div>
					<div class="table_cell cell_international_code"><?php echo __('Max'); ?></div>
					<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
				</div>
			</div>
			
			<?php
			echo $this->Utility->getListStart();
			foreach($options['options'] as $i => $values) {
				$isVisible = $values['visible']==1;
				$fieldName = sprintf('data[%s][%s][%%s]', $options['model'], $index++);
			
				echo $this->Utility->getListRowStart($i, $isVisible);
				echo $this->Utility->getIdInput($this->Form, $fieldName, $values['id']);
				echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
				// if there is any conditions, add as a hidden field
				if(isset($options['conditions'])) {
					foreach($options['conditions'] as $conditionName => $conditionValue) {
						echo $this->Form->input($conditionName, array(
							'type' => 'hidden',
							'name' => sprintf($fieldName, $conditionName),
							'value' => $conditionValue
						));
					}
				}
				echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
					echo $this->Form->input('name', array(
					'name' => sprintf($fieldName, 'name'),
					'maxlength' => 50,
					'readonly' => 'readonly',
					'value' => $values['name'],
					'before' => '<div class="cell cell_name"><div class="input_wrapper">',
					'after' => '</div></div>'
				));
				echo $this->Form->input('min', array(
					'name' => sprintf($fieldName, 'min'),
					'maxlength' => 30,
					'value' => $values['min'],
					'onkeypress'=> "return utility.integerCheck(event)",
					'onchange' => 'setCreditHourName("")',
					'before' => '<div class="cell cell_national_code"><div class="input_wrapper">',
					'after' => '</div></div>'
				));
				echo $this->Form->input('max', array(
					'name' => sprintf($fieldName, 'max'),
					'maxlength' => 30,
					'value' => $values['max'],
					'onkeypress'=> "return utility.integerCheck(event)",
					'onchange' => 'setCreditHourName("")',
					'before' => '<div class="cell cell_international_code"><div class="input_wrapper">',
					'after' => '</div></div>'
				));
				
				echo $this->Utility->getOrderControls();
				echo $this->Utility->getListRowEnd();
			}
			echo $this->Utility->getListEnd();
			if($_add) { echo $this->Utility->getAddRow('Option'); }
			?>
		</fieldset>
		<?php } ?>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'setupVariables', $selectedCategory), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>