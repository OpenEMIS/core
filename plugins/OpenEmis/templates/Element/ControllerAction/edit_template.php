<?php
$this->start('toolbar');
foreach ($toolbarButtons as $key => $btn) {
    if ($btn['type'] == 'button') {
        echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
    } else if ($btn['type'] == 'element') {
        echo $this->element($btn['element'], $btn['data'], $btn['options']);
    }
}
$this->end();

$this->start('panelBody');
if ($data) {
    if (isset($toolbarElements)) {
        foreach ($toolbarElements as $element) {
            echo $this->element($element['name'], $element['data'], $element['options']);
        }
    }

    $template = $this->ControllerAction->getFormTemplate();
    $formOptions = $this->ControllerAction->getFormOptions();
    $this->Form->templates($template);
    try {
        echo $this->Form->create($data, $formOptions);
    } catch (\Exception $exception) {

    }
    try {
        echo $this->ControllerAction->getEditElements($data);
    } catch (\Exception $exception) {

    }
    try {
        echo $this->ControllerAction->getFormButtons();
    } catch (\Exception $exception) {

    }
    echo $this->Form->end();
}
$this->end();

?>
