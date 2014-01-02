<?php
echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('/Students/js/students', false);
echo $this->Html->script('config', false);
$obj = @$data['Student'];
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="student" class="content_wrapper edit add">
	<h1>
		<span><?php echo __('Overview'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
		echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider')); 
		?>
	</h1>
	
	<?php
	echo $this->Form->create('Student', array(
		'url' => array('controller' => 'Students', 'action' => 'edit'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<fieldset class="section_break">
		<legend><?php echo __('Information'); ?></legend>
        <div class="row">
			<div class="label"><?php echo __('OpenEMIS ID'); ?></div>
			<?php if($autoid==''){ ?>
            <div class="value"><?php echo $this->Form->input('identification_no', array('value' => $obj['identification_no'],
														    'onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_identification');")); ?>
           		<input type="hidden" name="validate_student_identification" id="validate_student_identification" value="<?php echo $obj['identification_no']; ?>"/>
            </div>
            <?php }else{ ?>
            <div class="value"><?php echo $this->Form->input('identification_no', array('value' => $obj['identification_no'])); ?>
            </div>
            <?php } ?>
		</div>
		<div class="row">
			<div class="label"><?php echo  __('First Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('first_name', array('value' => $obj['first_name'])); ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo  __('Middle Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('middle_name', array('value' => $obj['middle_name'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo  __('Last Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('last_name', array('value' => $obj['last_name'])); ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo  __('Preferred Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('preferred_name', array('value' => $obj['preferred_name'])); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo  __('Gender'); ?></div>
			<div class="value">
			<?php echo $this->Form->input('gender', array(
				'options' => $gender,
				'default' => $obj['gender']));
			?>
			</div>
		</div>
		<div class="row">
			<div class="label"><?php echo  __('Date of Birth'); ?></div>
			<div class="value">
				<?php echo $this->Utility->getDatePicker($this->Form, 'date_of_birth', array('desc' => true,'value' => $obj['date_of_birth'], 'emptySelect' => true)); ?>
			</div>
		</div>
                <div class="row">
			<div class="label"><?php echo  __('Date of Death'); ?></div>
			<div class="value">
				<?php echo $this->Utility->getDatePicker($this->Form, 'date_of_death', array('desc' => true,'value' => $obj['date_of_death'], 'emptySelect' => true)); ?>
			</div>
		</div>
		<div class="row">
		    <div class="label"><?php echo __('Profile Image'); ?> </div>
		    <div class="value">
		        <?php echo $this->Form->input('photo_content', array('type' => 'file', 'class' => 'form-error'));?>
		        <?php echo $this->Form->hidden('reset_image', array('value'=>'0')); ?>
		        <span id="resetDefault" class="icon_delete"></span>
		        <?php echo isset($imageUploadError) ? '<div class="error-message">'.$imageUploadError.'</div>' : ''; ?>
		        <br/>
		        <div id="image_upload_info">
		            <em>
		                <?php echo sprintf(__("Max Resolution: %s pixels"), '400 x 514'); ?>
		                <br/>
		                <?php echo __("Max File Size:"). ' 200 KB'; ?>
		                <br/>
		                <?php echo __("Format Supported:"). " .jpg, .jpeg, .png, .gif"; ?>
		            </em>
				</div>
		    </div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Address'); ?></legend>
		<div class="row">
			<div class="label"><?php echo  __('Address'); ?></div>
			<div class="value"><?php echo $this->Form->input('address', array('value' => $obj['address'], 'onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo  __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code', array('value' => $obj['postal_code'],
														    'onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_postal_code');")); ?>
           		<input type="hidden" name="validate_student_postal_code" id="validate_student_postal_code" value="<?php echo $obj['postal_code']; ?>"/>
            </div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend id="area"><?php echo __('Address Area'); ?></legend>
			<?php echo @$this->Utility->getAreaPicker($this->Form, 'address_area_id',$obj['address_area_id'], array()); ?>
	</fieldset>
	
	<fieldset class="section_break">
		<legend id="area"><?php echo __('Birth Place Area'); ?></legend>
			<?php echo @$this->Utility->getAreaPicker($this->Form, 'birthplace_area_id',$obj['birthplace_area_id'], array()); ?>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo  __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone', array('value' => $obj['telephone'],
														    'onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_telephone');")); ?>
           		<input type="hidden" name="validate_student_telephone" id="validate_student_telephone" value="<?php echo $obj['telephone']; ?>"/>
            </div>
		</div>
		<div class="row">
			<div class="label"><?php echo  __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email', array('value' => $obj['email']));?></div>
		</div>
	</fieldset>

	<div class="controls view_controls">
		<input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'view'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>