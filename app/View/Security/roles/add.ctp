<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array('action' => 'roles');
if (!empty($selectedGroup)) {
	$params[] = $selectedGroup;
	$params['action'] = 'rolesUserDefined';
}
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));
$this->end();

$this->start('contentBody');
$formParams = array('controller' => $this->params['controller'], 'action' => 'rolesAdd', $roleType);
if (!empty($selectedGroup)) {
	$formParams[] = $selectedGroup;
}
$formOptions = $this->FormUtility->getFormOptions($formParams);
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('name');
echo $this->Form->input('security_group_id', array('options' => $groupOptions, 'default' => $selectedGroup, 'label' => array('text' => __('Groups'), 'class' => $labelOptions['class'])));
echo $this->Form->input('visible', array('options' => $yesnoOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => $params));
echo $this->Form->end();

$this->end();
?>
