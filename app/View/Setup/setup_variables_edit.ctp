<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="setup-variables" class="content_wrapper edit">
	<?php
	echo $this->Form->create('SetupVariables', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Setup', 'action' => 'setupVariablesEdit')
	));
	?>
	<h1>
		<span><?php echo __('Setup Variables'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'setupVariables'), array('id' => 'edit-link', 'class' => 'divider')); ?>
	</h1>
	
	<div class="row category">
		<?php
		echo $this->Form->input('category', array(
			'id' => 'category',
			'options' => $categoryList,
			'default' => $selectedCategory,
			'onchange' => 'setup.changeCategory()'
		));
		?>
	</div>
	<?php $index = 0; ?>
	<?php foreach($category as $type) { ?>
		<?php foreach($type['items'] as $typeName => $options) { ?>
		<fieldset class="section_group">
			<legend><?php echo __($typeName); ?></legend>
			<?php
			$model = explode('.', $options['model']);
			$model = sizeof($model) == 1 ? $model[0] : $model[1];
			echo $this->Form->hidden('model', array('id' => 'model', 'value' => $model));
			
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
					<div class="table_cell cell_order"><?php echo __('Order'); ?></div>
				</div>
			</div>
			
			<?php
			echo $this->Utility->getListStart();
			foreach($options['options'] as $i => $values) {
				$isVisible = $values['visible']==1;
				$fieldName = sprintf('data[%s][%s][%%s]', $model, $index++);
			
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
				echo $this->Utility->getNameInput($this->Form, $fieldName, $values['name'], $isNameEditable);
				echo $this->Utility->getOrderControls();
				echo $this->Utility->getListRowEnd();
			}
			echo $this->Utility->getListEnd();
			if($_add && $isAddAllowed) { echo $this->Utility->getAddRow('Option'); }
			?>
		</fieldset>
		<?php } ?>
	<?php } ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>