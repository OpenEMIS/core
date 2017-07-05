<?php
$this->extend('Page.Layout/container');

$this->start('toolbar');

echo $this->element('Page.button', ['url' => ['action' => 'index'], 'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back'], 'iconClass' => 'fa kd-back']);

$this->end();

$this->start('contentBody');

<<<<<<< HEAD
$formOptions = $this->Page->getFormOptions();
$template = $this->Page->getFormTemplate();
$this->Form->templates($template);
echo $this->Form->create($data, $formOptions);
echo $this->Page->renderInputElements();
echo $this->Page->getFormButtons();
echo $this->Form->end();
=======
<div class="panel">
    <div class="panel-body">
        <?php
        echo $this->element('Page.alert');
        $formOptions = $this->Page->getFormOptions();
        $template = $this->Page->getFormTemplate();
        $this->Form->templates($template);
        echo $this->Form->create($data, $formOptions);
        echo $this->Page->renderInputElements();
        echo $this->Page->getFormButtons();
        echo $this->Form->end();
        ?>
    </div>
</div>
>>>>>>> 92ed69a6b777b6ce5d15390aea6b89cec6f2ec6c

$this->end();
?>
