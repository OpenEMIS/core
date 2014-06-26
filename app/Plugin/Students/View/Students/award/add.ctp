<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('Staff.awards', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if (!$WizardMode) {
    if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'awardView', $this->data[$model]['id']);
    }
    else{
        $redirectAction = array('action' => 'award');
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Students'));
$formOptions['id'] = 'award';
$formOptions['selectAwardUrl']=$this->params['controller']."/awardAjaxFindAward/";
$labelOptions = $this->FormUtility->getLabelOptions();
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('issue_date');
echo $this->Form->input('award', array('id' => 'searchAward', 'class' => 'form-control award', 'label'=>array('text'=> $this->Label->get('general.name'),'class'=>$labelOptions['class'])));
echo $this->Form->input('issuer', array('id' => 'searchIssuer', 'class' => 'form-control issuer'));
echo $this->Form->input('comment');

if (!$WizardMode) {
	echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
} else {
	echo $this->FormUtility->getWizardButtons($WizardButtons);
}

echo $this->Form->end();
$this->end();
?>
