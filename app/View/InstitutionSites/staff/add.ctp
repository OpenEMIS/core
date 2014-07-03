<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('institution_site_staff', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Add Staff'));

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'InstitutionSites', 'action' => 'staffAdd'));
$formOptions['autocompleteURL'] = $this->params['controller'] . "/staffAjaxFind/";
$formOptions['inputDefaults']['autocomplete'] = 'off';
$labelOptions = $formOptions['inputDefaults']['label'];
$formOptions['id'] = 'staff_add';
echo $this->Form->create('InstitutionSiteStaff', $formOptions);
echo $this->Form->hidden('staff_id', array('id' => 'StaffId'));

$labelOptions['text'] = $this->Label->get('general.openemisId');
echo $this->Form->input('search', array('label' => $labelOptions, 'id' => 'staffNameAutoComplete', 'placeholder' => __('OpenEMIS ID, First Name or Last Name'),));

echo $this->Form->input('institution_site_position_id', array(
	'options' => $positionOptions,
	'default' => $selectedPositionId,
	'id' => 'institutionSitePositionId',
	'url' => 'InstitutionSites/staffAjaxRetriveUpdatedFTE',
	'onchange' => 'InstitutionSiteStaff.onPositioninfoChange(this)',
	'label' => array('text' => 'Position', 'class' => $labelOptions)
));
echo $this->FormUtility->datepicker('start_date', array('id' => 'startDate', 'onchange' => 'InstitutionSiteStaff.onPositioninfoChange(this)', 'url' => 'InstitutionSites/staffAjaxRetriveUpdatedFTE'));
echo $this->Form->input('FTE', array('id' => 'fte', 'label' => array('text' => 'FTE', 'class' => $labelOptions), 'options' => $FTEOtpions));
echo $this->Form->input('staff_type_id', array('label' => array('text' => $this->Label->get('general.type'), 'class' => $labelOptions), 'options' => $staffTypeOptions, 'default' => $staffTypeDefault));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'staff')));

echo $this->Form->end();
$this->end();
?>