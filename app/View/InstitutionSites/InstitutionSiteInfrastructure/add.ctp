<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Infrastructure') . ' - ' . $level['InfrastructureLevel']['name']);

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'InstitutionSiteInfrastructure', 'index', $levelId), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('action' => 'InstitutionSiteInfrastructure', 'add', $levelId));
$labelOptions = $formOptions['inputDefaults']['label'];

echo $this->Form->create('InstitutionSiteInfrastructure', $formOptions);
echo $this->Form->hidden('infrastructure_level_id', array('value' => $levelId));

if(!empty($parentLevel)){
	$labelOptions['text'] = __($parentLevel['InfrastructureLevel']['name']);
	echo $this->Form->input('parent_id', array(
		'options' => $parentInfraOptions,
		'label' => $labelOptions
	));
}

echo $this->Form->input('code');
echo $this->Form->input('name');

$labelOptions['text'] = $this->Label->get('InstitutionSiteInfrastructure.infrastructure_type_id');
echo $this->Form->input('infrastructure_type_id', array(
	'options' => $typeOptions,
	'label' => $labelOptions
));

$labelOptions['text'] = $this->Label->get('general.size');
echo $this->Form->input('size', array('label' => $labelOptions));

$labelOptions['text'] = $this->Label->get('InstitutionSiteInfrastructure.infrastructure_ownership_id');
echo $this->Form->input('infrastructure_ownership_id', array(
	'options' => $ownershipOptions,
	'label' => $labelOptions
));

echo $this->Form->input('year_acquired', array(
	'options' => $yearOptions,
	'value' => $currentYear
));
echo $this->Form->input('year_disposed', array(
	'options' => $yearDisposedOptions,
	'value' => $currentYear
));

$labelOptions['text'] = $this->Label->get('InstitutionSiteInfrastructure.infrastructure_condition_id');
echo $this->Form->input('infrastructure_condition_id', array(
	'options' => $conditionOptions,
	'label' => $labelOptions
));

echo $this->Form->input('comment', array('onkeyup' => 'utility.charLimit(this)', 'type' => 'textarea'));

echo $this->element('customfields/index', compact('model', 'modelOption', 'modelValue', 'modelRow', 'modelColumn', 'modelCell', 'action'));


echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'InstitutionSiteInfrastructure', 'index', $levelId)));
echo $this->Form->end();

$this->end(); 
?>