<?php
$this->extend('Page.Layout/container');

$this->start('toolbar');

echo $this->element('Page.button', ['url' => ['action' => 'index'], 'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back'], 'iconClass' => 'fa kd-back']);

$this->end();

$this->start('contentBody');

$formOptions = $this->Page->getFormOptions();
$template = $this->Page->getFormTemplate();
$this->Form->templates($template);
echo $this->Form->create($data, $formOptions);
echo $this->Page->renderInputElements();
echo $this->Page->getFormButtons();
echo $this->Form->end();

$this->end();
?>
