<?php
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_edit) {
    if(!empty($this->data[$model]['id'])){
        $redirectAction = array('action' => 'healthTestView', $this->data[$model]['id']);
        $setDate = array('data-date' => $this->data[$model]['date']);
    }
    else{
        $redirectAction = array('action' => 'healthTest');
        $setDate = null;
    }
    echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));
}
$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Students'));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->FormUtility->datepicker('date', $setDate);
echo $this->Form->input('health_test_type_id', array('options' => $healthTestsOptions, 'label'=>array('text'=> $this->Label->get('general.type'),'class'=>'col-md-3 control-label'))); 
echo $this->Form->input('result');
echo $this->Form->input('comment');

echo $this->FormUtility->getFormButtons(array('cancelURL' => $redirectAction));
echo $this->Form->end();
$this->end();
?>
