<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->Label->get("$model.title"));

$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, $_condition => $conditionId), array('class' => 'divider'));
if ($_edit) {
    echo $this->Html->link($this->Label->get('general.edit'), array('action' => $model, 'edit', $_condition => $conditionId, $data[$model]['id']), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->element('view');
$this->end();
?>
