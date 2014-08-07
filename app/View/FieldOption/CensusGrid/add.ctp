<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');

$params = array('action' => 'index', $selectedOption);
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));

$this->end();

$this->start('contentBody');

$formURL = array_merge($params, array('action' => 'add'));
$formOptions = $this->FormUtility->getFormOptions($formURL);
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('order', array('value' => 0));
echo $this->element('edit');
echo '<div class="col-md-offset-3" style="margin-bottom: 5px;">';
echo $this->Form->button('CensusGridXCategory', array('id' => 'CensusGridXCategory', 'type' => 'submit', 'name' => 'submit', 'value' => 'CensusGridXCategory', 'class' => 'hidden'));
echo $this->Form->button('CensusGridYCategory', array('id' => 'CensusGridYCategory', 'type' => 'submit', 'name' => 'submit', 'value' => 'CensusGridYCategory', 'class' => 'hidden'));
echo $this->Form->submit($this->Label->get("$model.update_preview"), array('name' => 'submit', 'value' => 'update', 'class' => 'btn_save btn_right', 'div' => false));
echo $this->Form->submit($this->Label->get('general.save'), array('name' => 'submit', 'class' => 'btn_save btn_right btn_left', 'div' => false));
echo $this->Html->link($this->Label->get('general.cancel'), $params, array('class' => 'btn_cancel btn_left'));
echo '</div>';
echo $this->Form->end();

$this->end();
?>
