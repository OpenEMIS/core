<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$action = array();
if (!empty($this->data['Translation']['id'])) {
	$action['action'] = 'view';
	$action[] = $this->data['Translation']['id'];
} else {
	$action['action'] = 'index';
}

echo $this->Html->link(__('Back'), $action, array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Translations', 'action' => $this->action, 'plugin' => 'Translations'));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Translation', $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('code');
$labelOptions['text'] = $this->Label->get('Translation.eng');
echo $this->Form->input('eng', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('Translation.ara');
echo $this->Form->input('ara', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('Translation.spa');
echo $this->Form->input('spa', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('Translation.chi');
echo $this->Form->input('chi', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('Translation.rus');
echo $this->Form->input('rus', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('Translation.fre');
echo $this->Form->input('fre', array('label'=>$labelOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
echo $this->Form->end();
$this->end();
?>
