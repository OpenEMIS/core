<?php
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('config', false);
echo $this->Html->script('institution', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add New Institution Site'));
$this->start('contentBody');
?>
<div id="site" class="content_wrapper edit add">

	<?php
	echo $this->Form->create('InstitutionSite', array(
		'url' => array('controller' => 'InstitutionSites', 'action' => 'add'),
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default')
	));
	echo $this->Form->hidden('institution_id',array('value'=>$institutionId));
	?>

	<fieldset class="section_break">
		<legend><?php echo __('General'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Site Name'); ?></div>
			<div class="value"><?php echo $this->Form->input('name'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Site Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_code');")) ?>
            <input type="hidden" name="validate_institution_site_code" id="validate_institution_site_code"/>
            </div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Type'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_type_id', array('options'=>$type_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Ownership'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_ownership_id', array('options'=>$ownership_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Status'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_status_id', array('options'=>$status_options)); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Opened'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_opened'); ?></div>
		</div>

		<div class="row">
			<div class="label"><?php echo __('Date Closed'); ?></div>
			<div class="value"><?php echo $this->Utility->getDatePicker($this->Form, 'date_closed', array('emptySelect'=>true)); ?></div>
		</div>

	</fieldset>
	<fieldset class="section_break">
        <legend id="area"><?php echo __('Area'); ?></legend>
        <?php echo @$this->Utility->getAreaPicker($this->Form, 'area_id','', array(), $filterArea); ?>
    </fieldset>
	<fieldset class="section_break">
        <legend id="education"><?php echo __('Area').' ('.__('Education').')'; ?></legend>
        <?php echo @$this->Utility->getAreaPicker($this->Form, 'area_education_id','', array()); ?>
    </fieldset>
	
	
	<fieldset class="section_break">
		<legend><?php echo __('Location'); ?></legend>
		<div class="row">
			<div class="label"><?php echo __('Address'); ?></div>
			<div class="value"><?php echo $this->Form->input('address', array('onkeyup' => 'utility.charLimit(this)')); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Postal Code'); ?></div>
			<div class="value"><?php echo $this->Form->input('postal_code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_postal_code');")) ?>
            <input type="hidden" name="validate_institution_site_postal_code" id="validate_institution_site_postal_code"/>
            </div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Locality'); ?></div>
			<div class="value"><?php echo $this->Form->input('institution_site_locality_id', array('options'=>$locality_options)); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Latitude'); ?></div>
			<div class="value"><?php echo $this->Form->input('latitude'); ?></div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Longitude'); ?></div>
			<div class="value"><?php echo $this->Form->input('longitude'); ?></div>
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
			<div class="value"><?php echo $this->Form->input('telephone', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_telephone');")) ?>
            <input type="hidden" name="validate_institution_site_telephone" id="validate_institution_site_telephone"/>
            </div>
		</div>
		<div class="row">
			<div class="label"><?php echo __('Fax'); ?></div>
			<div class="value"><?php echo $this->Form->input('fax', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_institution_site_fax');")) ?>
            <input type="hidden" name="validate_institution_site_fax" id="validate_institution_site_fax"/>
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
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="js:if(jsDate.checkValidDateClosed() && Config.checkValidate()){ return true; }else{ return false; }" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>