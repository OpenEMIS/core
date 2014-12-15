<?php
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('app.date', false);
echo $this->Html->script('app.area', false);
echo $this->Html->script('config', false);
echo $this->Html->script('institution', false);
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->script('plugins/inputmask/bootstrap-inputmask', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add new Institution'));
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'add'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('InstitutionSite', $formOptions);
?>

<fieldset class="section_break">
	<legend><?php echo $this->Label->get('general.general'); ?></legend>
	<?php
	echo $this->Form->input('name');
	echo $this->Form->input('alternative_name');
	echo $this->Form->input('code', $arrCode);

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_provider_id');
	echo $this->Form->input('institution_site_provider_id', array('options' => $providerOptions, 'label' => $labelOptions));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_sector_id');
	echo $this->Form->input('institution_site_sector_id', array('options' => $sectorOptions, 'label' => $labelOptions));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_type_id');
	echo $this->Form->input('institution_site_type_id', array('options' => $typeOptions, 'label' => $labelOptions));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_ownership_id');
	echo $this->Form->input('institution_site_ownership_id', array('options' => $ownershipOptions, 'label' => $labelOptions));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_gender_id');
	echo $this->Form->input('institution_site_gender_id', array('options' => $genderOptions, 'label' => $labelOptions));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_status_id');
	echo $this->Form->input('institution_site_status_id', array('options' => $statusOptions, 'label' => $labelOptions));

	echo $this->FormUtility->datepicker('date_opened', array('id' => 'dateOpened'));
	echo $this->FormUtility->datepicker('date_closed', array('id' => 'dateClosed', 'data-date' => false));
	?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $areaId)); ?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Area') . ' (' . __('Education') . ')'; ?></legend>
	<?php echo $this->FormUtility->areapicker('area_education_id', array('model' => 'AreaEducation', 'value' => $areaEducationId)); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Location'); ?></legend>
	<?php
	echo $this->Form->input('address', array('onkeyup' => 'utility.charLimit(this)', 'type' => 'textarea'));
	echo $this->Form->input('postal_code', array('onkeyup' => 'updateHiddenField(this, "validate_institution_site_postal_code")'));
	echo $this->Form->input('validate_institution_site_postal_code', array('type' => 'hidden', 'id' => 'validate_institution_site_postal_code'));
	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_locality_id');
	echo $this->Form->input('institution_site_locality_id', array('options' => $localityOptions, 'label' => $labelOptions));
	echo $this->Form->input('latitude');
	echo $this->Form->input('longitude');
	?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<?php
	echo $this->Form->input('contact_person');
	echo $this->Form->input('telephone', array('onkeyup' => 'updateHiddenField(this, "validate_institution_site_telephone")'));
	echo $this->Form->input('validate_institution_site_telephone', array('type' => 'hidden', 'id' => 'validate_institution_site_telephone'));
	echo $this->Form->input('fax', array('onkeyup' => 'updateHiddenField(this, "validate_institution_site_fax")'));
	echo $this->Form->input('validate_institution_site_fax', array('type' => 'hidden', 'id' => 'validate_institution_site_fax'));
	echo $this->Form->input('email');
	echo $this->Form->input('website');
	?>

</fieldset>

<div class="form-group">
	<div class="col-md-offset-4">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="js:if (Config.checkValidate()) {
					return true;
				} else {
					return false;
				}" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'index'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>
