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

$this->assign('contentId', 'student');	
$this->assign('contentHeader', __('Overview'));
$this->start('contentActions');
if (!$WizardMode) {
	echo $this->Html->link(__('View'), array('action' => 'view'), array('class' => 'divider'));
	echo $this->Html->link(__('History'), array('action' => 'history'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'add'));
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
		
		if ($autoid=='') {
			$arrIdNo = array_merge(array('label' => $openEmisIdLabel),$arrIdNo);
			echo $this->Form->input('openemis_no', $arrIdNo);
		} else {
			if ($this->Session->check('Student.id')) {
				echo $this->Form->input('openemis_no', array('label' => $openEmisIdLabel, 'readOnly' => true));
			} else {
				echo $this->Form->input('openemis_no', array('label' => $openEmisIdLabel, 'value' => $autoid, 'readOnly' => true));
			}
		}		

		// $selectAndTxtOptions = array(
		// 	'label' => __('Identity'),
		// 	'selectOptions' => $identityTypeOptions,
		// 	'selectId' => 'StudentIdentity.0.identity_type_id',
		// 	'txtId' => 'StudentIdentity.0.number',
		// 	'txtPlaceHolder' => __('Identity Number')
		// );

		// echo $this->element('templates/selectAndTxt', $selectAndTxtOptions);

		// echo $this->Form->input('StudentNationality.0.country_id', array('Label' => __('Nationality'), 'options' => $nationalityOptions));

		echo $this->Form->input('first_name');
		echo $this->Form->input('middle_name');
		echo $this->Form->input('third_name');
		echo $this->Form->input('last_name');
		echo $this->Form->input('preferred_name');
		echo $this->Form->input('gender_id', array('options' => $genderOptions));
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
		echo $this->Form->input('address', array('onkeyup' => 'utility.charLimit(this)'));
		echo $this->Form->input('postal_code', array('onkeyup'=>"javascript:updateHiddenField(this, 'validate_student_postal_code');"));
		$tempPostCode = isset($this->data[$model]['postal_code'])?$this->data[$model]['postal_code'] : '';
		echo $this->Form->hidden(null, array('id'=>'validate_student_postal_code', 'name' => 'validate_student_postal_code', 'value' => $tempPostCode));
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
if (isset($WizardMode) && $WizardMode) {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
} else {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'view')));
}
echo $this->Form->end();
$this->end();
?>
