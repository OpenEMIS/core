<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

//$data = $studentBehaviourObj[0]['StudentBehaviour'];
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour Details'));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'behaviour', $data['StudentBehaviour']['student_id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end(); ?>