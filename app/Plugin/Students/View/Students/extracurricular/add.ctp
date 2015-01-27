<?php

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->script('jquery-ui.min', false);
echo $this->Html->script('app.extracurricular', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

echo $this->Html->link($this->Label->get('general.back'), array('action' => 'extracurricular'), array('class' => 'divider'));

$this->end();
$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'extracurricularAdd', 'plugin'=>'Students'));
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('academic_period_id', array(
    'options' => $academicPeriodOptions,
    'selected' => $academicPeriodId)
);
echo $this->Form->input('extracurricular_type_id', array(
    'options' => $typeOptions
        )
);
echo $this->Form->input('name', array('class' => 'form-control autoComplete',  'label' => array('text'=> $this->Label->get('general.title'), 'class'=>'col-md-3 control-label'), 'url' => 'Students/extracurricularSearchAutoComplete'));
echo $this->FormUtility->datepicker('start_date', array('id' => 'StartDate'));
echo $this->FormUtility->datepicker('end_date', array('id' => 'EndDate', 'data-date' => date('d-m-Y', time() + 86400)));
echo $this->Form->input('hours', array('type' => 'number'));
echo $this->Form->input('points', array('type' => 'number'));
echo $this->Form->input('location');
echo $this->Form->input('comment', array('type' => 'textarea'));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'extracurricular')));
echo $this->Form->end();

$this->end();
?>
