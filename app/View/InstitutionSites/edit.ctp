<?php
echo $this->Html->script('app.date', false);
echo $this->Html->script('app.area', false);
echo $this->Html->script('institution_site', false);
echo $this->Html->script('config', false);
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');

$this->assign('contentId', 'site');
$this->assign('contentHeader', __('Overview'));
$this->assign('contentClass', 'edit add');
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'edit'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('InstitutionSite', $formOptions);
?>
<script>
	$(document).ready(function() {
		Config.applyRule();
	});
</script>

<?php $obj = @$data['InstitutionSite']; ?>

<fieldset class="section_break">
	<legend><?php echo __('Information'); ?></legend>
	<?php
	echo $this->Form->input('name', array('value' => $obj['name']));

	echo $this->Form->input('code', array(
		'onkeyup' => 'updateHiddenField(this, "validate_institution_site_code")',
		'value' => $obj['code']
	));
	echo $this->Form->input('validate_institution_site_code', array('type' => 'hidden', 'id' => 'validate_institution_site_code', 'value' => $obj['code']));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_provider_id');
	echo $this->Form->input('institution_site_provider_id', array('options' => $providerOptions, 'label' => $labelOptions, 'value' => $obj['institution_site_provider_id']));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_sector_id');
	echo $this->Form->input('institution_site_sector_id', array('options' => $sectorOptions, 'label' => $labelOptions, 'value' => $obj['institution_site_sector_id']));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_type_id');
	echo $this->Form->input('institution_site_type_id', array('options' => $typeOptions, 'label' => $labelOptions, 'value' => $obj['institution_site_type_id']));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_ownership_id');
	echo $this->Form->input('institution_site_ownership_id', array('options' => $ownershipOptions, 'label' => $labelOptions, 'value' => $obj['institution_site_ownership_id']));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_gender_id');
	echo $this->Form->input('institution_site_gender_id', array('options' => $genderOptions, 'label' => $labelOptions, 'value' => $obj['institution_site_gender_id']));
	
	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_status_id');
	echo $this->Form->input('institution_site_status_id', array('options' => $statusOptions, 'label' => $labelOptions, 'value' => $obj['institution_site_status_id']));

	echo $this->FormUtility->datepicker('date_opened', array('id' => 'dateOpened', 'data-date' => $obj['date_opened']));
	echo $this->FormUtility->datepicker('date_closed', array('id' => 'dateClosed', 'data-date' => $obj['date_closed']));
	?>
</fieldset>
<fieldset class="section_break">
	<legend><?php echo __('Location'); ?></legend>
	<?php
	echo $this->Form->input('address', array('type' => 'textarea', 'value' => $obj['address'], 'onkeyup' => 'utility.charLimit(this)'));
	echo $this->Form->input('postal_code', array(
		'onkeyup' => 'updateHiddenField(this, "validate_institution_site_postal_code")',
		'value' => $obj['postal_code']
	));
	echo $this->Form->input('validate_institution_site_postal_code', array('type' => 'hidden', 'id' => 'validate_institution_site_postal_code', 'value' => $obj['postal_code']));

	$labelOptions['text'] = $this->Label->get('InstitutionSite.institution_site_locality_id');
	echo $this->Form->input('institution_site_locality_id', array('options' => $localityOptions, 'label' => $labelOptions, 'value' => $obj['institution_site_locality_id']));
	echo $this->Form->input('latitude', array('value' => $obj['latitude']));
	echo $this->Form->input('longitude', array('value' => $obj['longitude']));
	?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('area_id', array('value' => $obj['area_id'])); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Area') . ' (' . __('Education') . ')'; ?></legend>
	<?php echo $this->FormUtility->areapicker('area_education_id', array('model' => 'AreaEducation', 'value' => $obj['area_education_id'])); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Contact'); ?></legend>
	<?php
	echo $this->Form->input('contact_person', array('value' => $obj['contact_person']));

	echo $this->Form->input('telephone', array(
		'onkeyup' => 'updateHiddenField(this, "validate_institution_site_telephone")',
		'value' => $obj['telephone']
	));
	echo $this->Form->input('validate_institution_site_telephone', array('type' => 'hidden', 'id' => 'validate_institution_site_telephone', 'value' => $obj['telephone']));

	echo $this->Form->input('fax', array(
		'onkeyup' => 'updateHiddenField(this, "validate_institution_site_fax")',
		'value' => $obj['fax']
	));
	echo $this->Form->input('validate_institution_site_fax', array('type' => 'hidden', 'id' => 'validate_institution_site_fax', 'value' => $obj['fax']));
	echo $this->Form->input('email', array('value' => $obj['email']));
	echo $this->Form->input('website', array('value' => $obj['website']));
	?>
</fieldset>

<div class="controls view_controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="js:if (Config.checkValidate()) {
			return true;
		} else {
			return false;
		}"/>
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'view'), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>
