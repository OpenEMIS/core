<?php
$this->extend('Page.Layout/container');

$this->start('contentBody');

$formOptions = $this->Page->getFormOptions();
$template = $this->Page->getFormTemplate();
$this->Form->templates($template);
echo $this->Form->create(!is_array($data) ? $data : null, $formOptions);
echo $this->Page->renderInputElements();
echo $this->Page->getFormButtons();
echo $this->Form->end();

$this->end();
?>
