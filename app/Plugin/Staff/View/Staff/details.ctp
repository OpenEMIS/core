<?php 
echo $this->Html->css('jquery_ui', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Staff/css/staff', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/staff', false);
echo $this->Html->script('jquery.ui', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="staff" class="content_wrapper">
	
	<h1>
		<span>Staff Details </span>
		<a class="void link-edit divider">Edit</a>
		<?php echo $this->Html->link('History', array('action' => 'history'), array('class' => 'divider')); ?>
		<?php //echo $this->Html->link('Edit', array('action' => 'staffEdit'), array('id' => 'edit-link')); ?>
	</h1>
	
	<?php
	echo $this->Form->create('Staff', array(
			'url' => array(
				'controller' => 'Staff', 
				'action' => 'details'
			),
			'model' => 'Staff'
			// 'inputDefaults' => array('label' => false, 'div' =>false)
		)
	);
	?>
	
	<fieldset class="section_break">
		<legend>General</legend>
		<div class="row">
			<div class="label">First Name</div>
			<div class="value" type="text" name="first_name"><?php echo $staff['Staff']['first_name']; ?></div>
		</div>
		<div class="row">
			<div class="label">Last Name</div>
			<div class="value" type="text" name="last_name"><?php echo $staff['Staff']['last_name']; ?></div>
		</div>
		<div class="row">
			<div class="label">Identification No</div>
			<div class="value" type="text" name="identification_no"><?php echo $staff['Staff']['identification_no']; ?></div>
		</div>
		<div class="row">
			<div class="label">Gender</div>
			<div class="value" type="text" name="gender"><?php echo $staff['Staff']['gender']; ?></div>
		</div>
		<div class="row">
			<div class="label">Date of Birth</div>
			<div class="value" type="text" name="first_name"><?php echo $staff['Staff']['date_of_birth']; ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend>Address</legend>
		<div class="row">
			<div class="label">Address</div>
			<div class="value" type="textarea" req="Address" name="address" style="width: 400px;"><?php echo $staff['Staff']['address']; ?></div>
		</div>
		<div class="row">
			<div class="label">Postal Code</div>
			<div class="value" req="Postal code" type="text" name="postal_code"><?php echo $staff['Staff']['postal_code']; ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend>Contact</legend>
		<div class="row">
			<div class="label">Telephone</div>
			<div class="value" type="text" name="telephone"><?php echo $staff['Staff']['telephone']; ?></div>
		</div>
		<div class="row">
			<div class="label">Email</div>
			<div class="value" type="email" name="email"><?php echo $staff['Staff']['email']; ?></div>
		</div>
	</fieldset>
	
	<?php echo $this->Form->end(); ?>
</div>
