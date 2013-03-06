<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('security', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('security', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="roles" class="content_wrapper edit">
	<?php
	echo $this->Form->create('Security', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'rolesEdit')
	));
	?>
	<h1>
		<span><?php echo __('Roles'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'roles'), array('class' => 'divider')); ?>
	</h1>
	
	<div class="table full_width" style="margin-bottom: 0">
		<div class="table_head">
			<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
			<div class="table_cell"><?php echo __('Role'); ?></div>
			<div class="table_cell cell_order"><?php echo __('Privilege Level'); ?></div>
		</div>
	</div>
		
	<ul class="quicksand table_view">
		<?php
		foreach($list as $i => $obj) {
			$isVisible = $obj['visible']==1;
			$fieldName = sprintf('data[SecurityRole][%s][%%s]', $i);
		
			echo $this->Utility->getListRowStart($i, $isVisible);
			echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
			echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));
			echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
			echo $this->Utility->getNameInput($this->Form, $fieldName, $obj['name']);
			echo $this->Utility->getOrderControls();
			echo $this->Utility->getListRowEnd();
		} ?>
	</ul>
	
	<?php 
	if($_add) {
		echo $this->Utility->getAddRow('Role');
	} 
	?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'roles'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>