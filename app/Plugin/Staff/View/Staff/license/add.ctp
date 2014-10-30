<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('Staff.licenses', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if (!$WizardMode) {
    if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'licenseView', $this->data[$model]['id']);
        $issueDate = array('id' => 'IssueDate' ,'data-date' => $this->data[$model]['issue_date']);
        $expiryDate = array('id' => 'ExpiryDate' ,'data-date' => $this->data[$model]['expiry_date']);
    }
    else{
        $redirectAction = array('action' => 'license');
        $issueDate = array('id' => 'IssueDate');
        $expiryDate = array('id' => 'ExpiryDate' ,'data-date' => date('d-m-Y', time() + 86400));
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}else{
	$issueDate = array('id' => 'IssueDate');
    $expiryDate = array('id' => 'ExpiryDate' ,'data-date' => date('d-m-Y', time() + 86400));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action));
$formOptions['id'] = 'license';
$formOptions['selectLicenseUrl'] = $this->params['controller']."/licenseAjaxFindLicense/";
$labelOptions = $this->FormUtility->getLabelOptions();
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('issue_date', $issueDate);
echo $this->Form->input('license_type_id', array('options'=>$licenseTypeOptions, 'label'=>array('text'=> $this->Label->get('general.type'),'class'=>$labelOptions['class'])));
echo $this->Form->input('issuer', array('id' => 'searchIssuer', 'class' => 'form-control issuer'));
echo $this->Form->input('license_number');
echo $this->FormUtility->datepicker('expiry_date', $expiryDate);

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();
$this->end();
?>
