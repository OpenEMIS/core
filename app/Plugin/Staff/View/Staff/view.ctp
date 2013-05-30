<?php 
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff" class="content_wrapper">
	
	<h1>
		<span><?php echo __('Staff Information'); ?></span>
		<?php 
		if ($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'edit'), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	
	<?php $obj = $data['Staff']; ?>
	
	<fieldset class="section_break" id="general">
		<legend><?php echo __('General'); ?></legend>
		<?php
		    $path = (isset($obj['photo_content']) && !empty($obj['photo_content']) && !stristr($obj['photo_content'], 'null'))? "/Staff/fetchImage/{$obj['id']}":"/Staff/img/default_staff_profile.jpg";
		    echo $this->Html->image($path, array('class' => 'profile_image', 'alt' => '90x115'));
		?>
		<?php // echo $this->Html->image("/Staff/img/default_staff_profile.jpg", array('class' => 'profile_image', 'alt' => '90x115')); ?>
		<div class="row">
			<div class="label"><?php echo __('Identification No.'); ?></div>
			<div class="value"><?php echo $obj['identification_no']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $obj['first_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $obj['last_name']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Utility->formatGender($obj['gender']); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value"><?php echo $this->Utility->formatDate($obj['date_of_birth']); ?></div>
		</div>
	</fieldset>

	<fieldset class="section_break">
		<legend><?php echo __('Address'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value address"><?php echo nl2br($obj['address']); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $obj['postal_code']; ?></div>
		</div>
	</fieldset>
	
	</fieldset>
		<fieldset class="section_break">
		<legend><?php echo __('Address Area'); ?></legend>   
		<?php echo $this->Utility->showArea($this->Form, 'address_area_id',@$obj['address_area_id'], array()); ?>
	</fieldset>

	<fieldset class="section_break">
		<legend><?php echo __('Birth Place Area'); ?></legend>   
		<?php echo $this->Utility->showArea($this->Form, 'birthplace_area_id',@$obj['birthplace_area_id'], array()); ?>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $obj['telephone']; ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $obj['email']; ?></div>
		</div>
	</fieldset>
	
	<?php echo $this->Form->end(); ?>
</div>
