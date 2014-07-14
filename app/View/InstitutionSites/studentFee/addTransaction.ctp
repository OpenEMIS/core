<?php
echo $this->Html->script('institution_site_fee', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'studentFeeView', $studentId, $feeId), array('class' => 'divider', 'id'=>'back'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action']));
echo $this->Form->create($model, $formOptions);
echo isset($this->request->data[$model][0]['id']) ? $this->Form->hidden($model.'.0.id') : '';
echo $this->FormUtility->datepicker($model.'.0.paid_date', array('label' => $this->Label->get('general.date'), 'id' => 'paidDate', 'data-date' => (isset($this->request->data[$model][0]['paid_date']) ? $this->request->data[$model][0]['paid_date'] : date('Y-m-d'))));
echo $this->Form->input($model.'.0.paid', array('min'=>'0', 'step'=>'1', 'pattern'=>'\d+', 'label' => array('text' => sprintf('%s (%s)',$this->Label->get('FinanceFee.amount'), $currency), 'class' => 'col-md-3 control-label')));
echo $this->Form->input($model.'.0.comments', array('type' => 'textarea'));
?>
<?php 
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'studentFeeView', $studentId, $feeId)));
echo $this->Form->end();

$this->end();
?>
