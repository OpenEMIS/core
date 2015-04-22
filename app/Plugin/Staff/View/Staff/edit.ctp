<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->script('plugins/inputmask/bootstrap-inputmask', false);
echo $this->Html->script('holder', false);
echo $this->Html->script('app.date', false);
echo $this->Html->script('app.area', false);
echo $this->Html->script('config', false);

$this->extend('/Elements/layout/container');

$this->assign('contentId', 'staff');
$this->assign('contentHeader', __('Overview'));
$this->start('contentActions');
if (!$WizardMode) {
	echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
	echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'edit'));
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['id'] = $model;
$formOptions['type'] = 'file';
echo $this->Form->create($model, $formOptions);
?>

<fieldset class="section_break">
	<legend><?php echo __('Information'); ?></legend>
	<?php 
		$openEmisIdLabel = $labelOptions;
		$openEmisIdLabel['text'] = $this->Label->get('general.openemisId');

		if (isset($this->data[$model]['id'])) {
			echo $this->Form->hidden('id', array('value' => $this->data[$model]['id']));
		}
		
		if ($this->Session->check('Staff.id')) {
			echo $this->Form->input('SecurityUser.openemis_no', array('label' => $openEmisIdLabel, 'readOnly' => true));
		} else {
			echo $this->Form->input('SecurityUser.openemis_no', array('label' => $openEmisIdLabel, 'value' => $autoid, 'readOnly' => true));
		}

		if (isset($nationalityOptions)) {
			echo $this->Form->input('StaffNationality.0.country_id', array('Label' => __('Nationality'), 'options' => $nationalityOptions, 'onchange' => "$('#reload').val('changeNationality').click()"));
		}
		if (isset($identityTypeOptions)) {
			$selectAndTxtOptions = array(
				'label' => __('Identity'),
				'selectOptions' => $identityTypeOptions,
				'selectId' => 'StaffIdentity.0.identity_type_id',
				'txtId' => 'StaffIdentity.0.number',
				'txtPlaceHolder' => __('Identity Number')
			);
			echo $this->element('templates/selectAndTxt', $selectAndTxtOptions);
		}
		
		echo $this->Form->input('SecurityUser.first_name');
		echo $this->Form->input('SecurityUser.middle_name');
		echo $this->Form->input('SecurityUser.third_name');
		echo $this->Form->input('SecurityUser.last_name');
		echo $this->Form->input('SecurityUser.preferred_name');
		echo $this->Form->input('SecurityUser.gender_id', array('options' => $genderOptions));
		$tempDob = isset($this->data[$model]['date_of_birth']) ? array('data-date' => $this->data[$model]['date_of_birth']) : array();
		echo $this->FormUtility->datepicker('date_of_birth', $tempDob);
		
		$imgOptions = array();
		$imgOptions['field'] = 'photo_content';
		$imgOptions['width'] = '90';
		$imgOptions['height'] = '115';
		$imgOptions['label'] = __('Profile Image');
		if (isset($this->data['security_user']['photo_name']) && isset($this->data['security_user']['photo_content'])) {
			$imgOptions['src'] = $this->Image->getBase64($this->data['security_user']['photo_name'], $this->data['security_user']['photo_content']);
		}
		echo $this->element('templates/file_upload_preview', $imgOptions);
	?>
	<div class="form-group">
		<div class="col-md-3"></div>
		<div class="col-md-6">
			<?php echo __("Format Supported:") . " .jpg, .jpeg, .png, .gif"; ?>
		</div>
	</div>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Address'); ?></legend>
	<?php 
		echo $this->Form->input('SecurityUser.address', array('onkeyup' => 'utility.charLimit(this)'));
		echo $this->Form->input('SecurityUser.postal_code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_staff_postal_code');"));
		$tempPostCode = isset($this->data[$model]['postal_code'])?$this->data[$model]['postal_code'] : '';
		echo $this->Form->hidden(null, array('id'=>'validate_staff_postal_code', 'name' => 'validate_staff_postal_code', 'value' => $tempPostCode));
	?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Address Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('address_area_id', array('id' => 'area_address_picker', 'model' => 'AreaAdministrative', 'value' => $addressAreaId)); ?>
</fieldset>

<fieldset class="section_break">
	<legend><?php echo __('Birth Place Area'); ?></legend>
	<?php echo $this->FormUtility->areapicker('birthplace_area_id', array('id' => 'area_birthplace_picker', 'model' => 'AreaAdministrative', 'value' => $birthplaceAreaId)); ?>
</fieldset>

<?php 
if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view')));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}
echo $this->Form->end();
$this->end();
?>
