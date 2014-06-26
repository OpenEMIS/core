<?php
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('institution', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="institutionAdd" class="content_wrapper edit add">
	<h1><?php echo __('Add Institution'); ?></h1>
	
	<?php
	echo $this->Form->create('Institution', array(
		'url' => array('controller' => 'Institutions', 'action' => 'add'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	?>
	
	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Institution Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Institution Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_code');")) ?>
            <input type="hidden" name="validate_institution_code" id="validate_institution_code"/>
            </div>
		</div>
        <div class="row">
			<div class="label"><?php echo __('Sector'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_sector_id', array('options'=>$sector_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Provider'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_provider_id', array('options'=>$provider_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_status_id', array('options'=>$status_options));  ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Date Opened'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_opened'); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Closed'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_closed', array('emptySelect'=>true));?></div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value"><?php echo $this->Form->input('address', array('onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_postal_code');")); ?>
            <input type="hidden" name="validate_institution_postal_code" id="validate_institution_postal_code"/>
            </div>
		</div>
	</fieldset>
	
	<fieldset class="section_break">
		<legend><?php echo __('Contact'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Contact Person'); ?></div>
			<div class="value"><?php echo $this->Form->input('contact_person'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Telephone'); ?></div>
			<div class="value"><?php echo $this->Form->input('telephone', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_telephone');")); ?>
           	<input type="hidden" name="validate_institution_telephone" id="validate_institution_telephone"/>
            </div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Fax'); ?></div>
			<div class="value"><?php echo $this->Form->input('fax', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_fax');")); ?>
           	<input type="hidden" name="validate_institution_fax" id="validate_institution_fax"/>
            </div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Email'); ?></div>
			<div class="value"><?php echo $this->Form->input('email'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Website'); ?></div>
			<div class="value"><?php echo $this->Form->input('website'); ?></div>
		</div>
	</fieldset>
	
	<div class="controls view_controls">
		<input type="submit" value="<?php echo __('Save'); ?>"  onclick="js:if(jsDate.checkValidDateClosed() && Config.checkValidate()){ return true; }else{ return false; }" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
