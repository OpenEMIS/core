<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('Staff.memberships', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
    if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'membershipView', $this->data[$model]['id']);
        $startDate = array('id' => 'issueDate' ,'data-date' => $this->data[$model]['issue_date']);
        $endDate = array('id' => 'expiryDate' ,'data-date' => $this->data[$model]['expiry_date']);
    }
    else{
        $redirectAction = array('action' => 'membership');
        $startDate = array('id' => 'issueDate');
        $endDate = array('id' => 'expiryDate' ,'data-date' => date('d-m-Y', time() + 86400));
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}else{
	$redirectAction = array('action' => 'membership');
    $startDate = array('id' => 'issueDate');
    $endDate = array('id' => 'expiryDate' ,'data-date' => date('d-m-Y', time() + 86400));
}
$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
$formOptions['id'] = 'membership';
$formOptions['selectMembershipUrl']=$this->params['controller']."/membershipsAjaxFindMembership/";
$labelOptions = $this->FormUtility->getLabelOptions();
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('issue_date', $startDate);
echo $this->Form->input('membership', array('id' => 'searchMembership', 'class' => 'form-control membership', 'label'=>array('text'=> $this->Label->get('general.name'),'class'=>$labelOptions['class'])));
echo $this->FormUtility->datepicker('expiry_date', $endDate);
echo $this->Form->input('comment');
if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
}else{
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();
$this->end();
?>
