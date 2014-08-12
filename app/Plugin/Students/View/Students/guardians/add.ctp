<?php
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('guardian', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'guardians'), array('class' => 'divider'));
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'guardiansAdd'));
echo $this->Form->create($model, $formOptions);

$obj = $this->request->data; 
echo $this->Form->input('Guardian.existing_id', array('type' => 'hidden', 'value' => isset($obj['Guardian']['existing_id']) ? $obj['Guardian']['existing_id'] : 0));
echo $this->Form->input('Search.search', array('id' => 'SearchGuardianName'));
echo $this->Form->input('StudentGuardian.guardian_relation_id', array('options' => $relationshipOptions));
echo $this->Form->input('Guardian.first_name'); 
echo $this->Form->input('Guardian.last_name');
echo $this->Form->input('Guardian.gender', array('options' => $genderOptions));
echo $this->Form->input('Guardian.mobile_phone');
echo $this->Form->input('Guardian.office_phone'); 
echo $this->Form->input('Guardian.email'); 
echo $this->Form->input('Guardian.address', array('type' => 'textarea'));
echo $this->Form->input('Guardian.postal_code');
echo $this->Form->input('Guardian.guardian_education_level_id', array('options' => $educationOptions));
echo $this->Form->input('Guardian.comments', array('type' => 'textarea'));

echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'guardians')));
echo $this->Form->end();

$this->end();
?>
