<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));

echo $this->Html->script('app.date', false);
echo $this->Html->script('institution_site_staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staffAdd" class="content_wrapper edit">
	<?php
	echo $this->Form->create('InstitutionSiteStaff', array(
		'id' => 'submitForm',
		'onsubmit' => 'return InstitutionSiteStaff.validateStaffAdd()',
		'inputDefaults' => array('label' => false, 'div' => false, 'autocomplete' => 'off'),
		'url' => array('controller' => 'InstitutionSites', 'action' => 'staffAdd')
	));
	echo $this->Form->hidden('staff_id', array('id' => 'StaffId', 'value' => 0));
	?>
	<h1>
		<span><?php echo __('Add Staff'); ?></span>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<div class="info" url="InstitutionSites/staffView/">
		<div class="row">
			<div class="label"><?php echo __('Identification No'); ?></div>
			<div class="value"><?php echo $this->Form->input('identification_no', array('class' => 'default', 'id' => 'IdentificationNo')); ?></div>
			<span class="left icon_search" url="InstitutionSites/staffSearch"></span>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('first_name', array('class' => 'default', 'id' => 'FirstName', 'disabled' => 'disabled')); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('last_name', array('class' => 'default', 'id' => 'LastName', 'disabled' => 'disabled')); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Form->input('gender', array('class' => 'default', 'id' => 'Gender', 'disabled' => 'disabled')); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Position'); ?></div>
			<div class="value"><?php echo $this->Form->input('staff_category_id', array('class' => 'default', 'options' => $categoryOptions)); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Start Date'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'start_date'); ?></div>
		</div>
		
		<div class="row">
			<div class="label"><?php echo __('Salary'); ?></div>
			<div class="value"><?php echo $this->Form->input('salary', array('class' => 'default', 'value' => 0)); ?></div>
		</div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Add'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'staff'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>