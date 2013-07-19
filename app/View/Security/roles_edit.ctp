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
	echo $this->Form->create('SecurityGroup', array(
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Security', 'action' => 'rolesEdit', $selectedGroup)
	));
	?>
	<h1>
		<span><?php echo __('Roles'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'roles', $selectedGroup), array('class' => 'divider')); ?>
	</h1>
	
	<?php if(AuthComponent::user('super_admin')==1) { ?>
	<fieldset class="section_group">
		<legend><?php echo __('System Defined Roles'); ?></legend>
		<?php echo $this->Form->hidden('security_group_id', array('id' => 'SecurityGroupId', 'value' => 0)); ?>
		<div class="table full_width" style="margin-bottom: 0; margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
				<div class="table_cell"><?php echo __('Role'); ?></div>
				<div class="table_cell cell_order"><?php echo __('Privilege'); ?></div>
			</div>
		</div>
		
		<ul class="quicksand table_view">
			<?php
			foreach($systemRoles as $i => $obj) {
				$isVisible = $obj['visible']==1;
				$isSystemRole = $obj['security_group_id'] == -1;
				$fieldName = sprintf('data[SecurityRole][%s][%%s]', $i);
				
				echo $this->Utility->getListRowStart($i, $isVisible);
				echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']);
				echo $this->Utility->getOrderInput($this->Form, $fieldName, ($i+1));			
				if($isSystemRole) {
					$name = $obj['name'] . ' (Not Editable)';
					$options = array(
						'name' => sprintf($fieldName, 'visible'),
						'type' => 'checkbox',
						'autocomplete' => 'off',
						'disabled' => 'disabled',
						'before' => '<div class="cell cell_visible">',
						'after' => '</div>'
					);
					
					if($obj['visible']==1) {
						$options['checked'] = 'checked';
					}
					echo $this->Form->input('visible', $options);
					echo $this->Utility->getNameInput($this->Form, $fieldName, $name, false);
				} else {
					echo $this->Utility->getVisibleInput($this->Form, $fieldName, $isVisible);
					echo $this->Utility->getNameInput($this->Form, $fieldName, $obj['name']);
					
				}
				echo $this->Utility->getOrderControls();
				echo $this->Utility->getListRowEnd();
			} ?>
		</ul>
		<?php if($_add) { ?>
		<div class="row">
			<a class="void icon_plus" url="Security/rolesAdd" onclick="Security.addRole(this)"><?php echo __('Add').' '.__('Role'); ?></a>
		</div>
		<?php } ?>
	</fieldset>
	<?php } ?>
	
	<?php if(!empty($groupOptions)) { ?>
	<fieldset class="section_group">
		<legend><?php echo __('User Defined Roles'); ?></legend>
		
		<div class="row" style="margin: 0 0 10px 10px; line-height: 25px;">
			<div class="label" style="width: 60px;"><?php echo __('Group'); ?></div>
			<div class="value">
				<?php
				echo $this->Form->input('security_group_id', array(
					'id' => 'SecurityGroupId',
					'options' => $groupOptions,
					'default' => $selectedGroup,
					'url' => $this->params['controller'] . '/' . $this->params['action'],
					'onchange' => 'jsForm.change(this)'
				));
				?>
			</div>
		</div>
		
		<div class="table full_width" style="margin-bottom: 0; margin-top: 10px;">
			<div class="table_head">
				<div class="table_cell cell_visible"><?php echo __('Visible'); ?></div>
				<div class="table_cell"><?php echo __('Role'); ?></div>
				<div class="table_cell cell_order"><?php echo __('Privilege'); ?></div>
			</div>
		</div>
		
		<ul class="quicksand table_view">
			<?php
			foreach($userRoles as $i => $obj) {
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
		<?php if($_add) { ?>
		<div class="row">
			<a class="void icon_plus" url="Security/rolesAdd" onclick="Security.addRole(this)"><?php echo __('Add').' '.__('Role'); ?></a>
		</div>
		<?php } ?>
	</fieldset>
	<?php } // end if ?>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'roles', $selectedGroup), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>