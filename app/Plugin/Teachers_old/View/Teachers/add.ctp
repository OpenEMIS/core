<?php
echo $this->Html->css('/Teachers/css/teachers', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('/Teachers/js/teachers', false);
$obj = @$data['Teacher'];
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="teacherAdd" class="content_wrapper edit add">
	<h1><?php echo __('Add new Teacher'); ?></h1>

	<?php
	echo $this->Form->create('Teacher', array(
		'url' => array('controller' => 'Teachers', 'action' => 'add'),
                'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	?>
	
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
        <div class="row">
			<div class="label"><?php echo __('OpenEMIS ID'); ?>
            <?php if($autoid!=''){ ?>
            <?php echo $this->Form->input('identification_no', array('hidden'=>true,  'default'=>$autoid, 'error' => false)); ?>
            <?php } ?>
            </div>
            <div class="value">
            <?php if($autoid!=''){ ?>
            	 <?php echo $autoid; ?>
            <?php }else{ ?>
                <?php echo $this->Form->input('identification_no', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_teacher_identification');")) ?>
            	<input type="hidden" name="validate_teacher_identification" id="validate_teacher_identification"/>
            <?php } ?>
            </div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('First Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('first_name'); ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Middle Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('middle_name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Last Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('last_name'); ?></div>
		</div>
                <div class="row">
			<div class="label"><?php echo __('Preferred Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('preferred_name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Gender'); ?></div>
			<div class="value"><?php echo $this->Form->input('gender', array('options' => $gender));  ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date of Birth'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_of_birth',array('desc' => true, 'emptySelect' => true)); ?></div>
		</div>
                <?php /*<div class="row">
			<div class="label"><?php echo __('Date of Death'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_of_death',array('desc' => true, 'emptySelect' => true)); ?></div>
		</div>*/ ?>
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
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Address'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value"><?php echo $this->Form->input('address', array('type' => 'textarea', 'onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_teacher_postal_code');")) ?>
            <input type="hidden" name="validate_teacher_postal_code" id="validate_teacher_postal_code"/>
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

	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>