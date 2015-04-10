<?php
//echo $this->Html->css('/Students/css/students', 'stylesheet', array('inline' => false));
//echo $this->Html->script('/Students/js/students', false);
?>
<?php $obj = $data[$modelName];

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Utility->ellipsis(__($subheader), 50));
$this->start('contentActions');
 echo $this->Html->link($this->Label->get('general.back'), array('action' => 'rubricsTemplatesHeader', $rubric_template_id), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => 'rubricsTemplatesHeaderEdit', $rubric_template_id, $obj['id']), array('class' => 'divider'));
}

if ($_delete && !$disableDelete) {
    echo $this->Html->link($this->Label->get('general.delete'), array('action' => 'rubricsTemplatesHeaderDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
}
$this->end();

$this->start('contentBody'); 
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
$this->end(); 
?>