<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link(__('Back'), array('action' => 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => 'Translations', 'action' => $this->action));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create('Translation', $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->input('code');
$labelOptions['text'] = $this->Label->get('general.language.eng');
echo $this->Form->input('eng', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('general.language.ara');
echo $this->Form->input('ara', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('general.language.spa');
echo $this->Form->input('spa', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('general.language.chi');
echo $this->Form->input('chi', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('general.language.rus');
echo $this->Form->input('rus', array('label'=>$labelOptions));
$labelOptions['text'] = $this->Label->get('general.language.fre');
echo $this->Form->input('fre', array('label'=>$labelOptions));
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => 'index')));
echo $this->Form->end();
$this->end();
?>
