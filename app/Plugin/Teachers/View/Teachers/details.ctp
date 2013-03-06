<?php 
echo $this->Html->css('jquery_ui', 'stylesheet', array('inline' => false));
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Teachers/js/teachers', false);
echo $this->Html->script('jquery.ui', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teacher" class="content_wrapper">
	
	<h1>
		<span>Teacher Details </span>
		<a class="void link-edit divider">Edit</a>
		<?php echo $this->Html->link('History', array('action' => 'history'), array('class' => 'divider')); ?>
		<?php //echo $this->Html->link('Edit', array('action' => 'teachersEdit'), array('id' => 'edit-link')); ?>
	</h1>
	
	<?php
	echo $this->Form->create('Teacher', array(
			'url' => array(
				'controller' => 'Teachers', 
				'action' => 'details'
			),
			'model' => 'Teachers'
			// 'inputDefaults' => array('label' => false, 'div' =>false)
		)
	);
	?>
	
	<fieldset class="section_break">
		<legend>General</legend>
		<div class="row">
			<div class="label">First Name</div>
			<div class="value" type="text" name="first_name"><?php echo $teacher['Teacher']['first_name']; ?></div>
		</div>
		<div class="row">
			<div class="label">Last Name</div>
			<div class="value" type="text" name="last_name"><?php echo $teacher['Teacher']['last_name']; ?></div>
		</div>
		<div class="row">
			<div class="label">Identification No</div>
			<div class="value" type="text" name="identification_no"><?php echo $teacher['Teacher']['identification_no']; ?></div>
		</div>
		<div class="row">
			<div class="label">Gender</div>
			<div class="value" type="text" name="gender"><?php echo $teacher['Teacher']['gender']; ?></div>
		</div>
		<div class="row">
			<div class="label">Date of Birth</div>
			<div class="value" type="text" name="first_name"><?php echo $teacher['Teacher']['date_of_birth']; ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend>Address</legend>
		<div class="row">
			<div class="label">Address</div>
			<div class="value" type="textarea" req="Address" name="address" style="width: 400px;"><?php echo $teacher['Teacher']['address']; ?></div>
		</div>
		<div class="row">
			<div class="label">Postal Code</div>
			<div class="value" req="Postal code" type="text" name="postal_code"><?php echo $teacher['Teacher']['postal_code']; ?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend>Contact</legend>
		<div class="row">
			<div class="label">Telephone</div>
			<div class="value" type="text" name="telephone"><?php echo $teacher['Teacher']['telephone']; ?></div>
		</div>
		<div class="row">
			<div class="label">Email</div>
			<div class="value" type="email" name="email"><?php echo $teacher['Teacher']['email']; ?></div>
		</div>
	</fieldset>
	
	<?php echo $this->Form->end(); ?>
</div>