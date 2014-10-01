<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Behaviour Details'));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'behaviourStudent', $data[$model]['student_id']), array('class' => 'divider'));
//if ($institutionSiteId == $data['institution_site_id']) {
	if ($_edit) {
		echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'behaviourStudentEdit', $studentId,$data[$model]['id']), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'behaviourStudentDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
//}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end(); ?>

