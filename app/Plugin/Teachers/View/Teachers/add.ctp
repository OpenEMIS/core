<?php
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('/Teachers/js/teachers', false);
$obj = @$data['Teacher'];
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teacherAdd" class="content_wrapper edit add">
	<h1><?php echo __('Add new Teacher'); ?></h1>

	<?php
	echo $this->Form->create('Teacher', array(
		'url' => array('controller' => 'Teachers', 'action' => 'add'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	?>
	
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Identification No.'); ?></div>
			<div class="value"><?php echo $this->Form->input('identification_no'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('first_name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('last_name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Form->input('gender', array('options' => $gender));  ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_of_birth',array('desc' => true)); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Address'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value"><?php echo $this->Form->input('address', array('type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code'); ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Address Area'); ?></legend>   
			<?php echo $this->Utility->getAreaPicker($this->Form, 'address_area_id',@$obj['address_area_id'], array()); ?>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Birth Place Area'); ?></legend>   
			<?php echo $this->Utility->getAreaPicker($this->Form, 'birthplace_area_id',@$obj['birthplace_area_id'], array()); ?>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email'); ?></div>
		</div>
	</fieldset>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>