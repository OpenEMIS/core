<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
//if(!empty($this->data[$model]['id'])){
	$startDate = array('id' => 'startDate');
	$endDate = array('id' => 'endDate');
		
	$redirectAction = array('action' => 'positionsHistory', $this->data['InstitutionSitePosition']['id']);
	$startDate['data-date'] = $this->data['InstitutionSiteStaff']['start_date'];
    $endDate['data-date'] = empty($this->data['InstitutionSiteStaff']['end_date'])?date('d-m-Y', strtotime($this->data['InstitutionSiteStaff']['start_date']) + 86400):$this->data['InstitutionSiteStaff']['end_date'];
/*}
else{
	$redirectAction = array('action' => 'positions');
}*/
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
if ($_delete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'staffPositionDelete', $this->data['InstitutionSitePosition']['id']), array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');


$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, $this->params['pass'][0]));
$labelDefault = $formOptions;
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('InstitutionSiteStaff.id');

$name = $this->data['Staff']['first_name'].' '.$this->data['Staff']['middle_name'].' '.$this->data['Staff']['last_name'] ;
echo $this->Form->input('StaffPositionTitle.name', array('disabled' => 'disabled', 'label' => array('text' => $this->Label->get('general.title'), 'class'=>"col-md-3 control-label" )));
echo $this->Form->input('Staff.name', array('disabled' => 'disabled', 'value' => $name));
echo $this->FormUtility->datepicker('InstitutionSiteStaff.start_date', $startDate);
echo $this->FormUtility->datepicker('InstitutionSiteStaff.end_date', $endDate);
echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
