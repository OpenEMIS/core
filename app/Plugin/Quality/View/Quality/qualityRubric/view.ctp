<?php
$this->extend('/Elements/layout/container');

$this->assign('contentHeader', $header);

$obj = $data['QualityInstitutionRubric'];

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => 'qualityRubric'), array('class' => 'divider'));
echo $this->Html->link($this->Label->get('Quality.view_rubric'), array('action' => 'qualityRubricHeader', $obj['id'], $rubric_template_id), array('class' => 'divider'));

if ($_edit) {
	echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'qualityRubricEdit', $obj['id']), array('class' => 'divider'));
}

if ($_delete && !$disableDelete) {
	echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'qualityRubricDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end(); ?>