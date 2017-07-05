<?php
$this->extend('Page.Layout/container');

$primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

$this->start('toolbar');

echo $this->element('Page.button', ['url' => ['action' => 'view', $primaryKey], 'iconClass' => 'fa kd-back', 'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']]);
echo $this->element('Page.button', ['url' => ['action' => 'index'], 'iconClass' => 'fa kd-lists', 'linkOptions' => ['title' => __('List')]]);

$this->end();

$this->start('contentBody');

<<<<<<< HEAD
$formOptions = $this->Page->getFormOptions();
$template = $this->Page->getFormTemplate();
$this->Form->templates($template);
echo $this->Form->create(!is_array($data) ? $data : null, $formOptions);
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
        echo $this->Form->create(!is_array($data) ? $data : null, $formOptions);
        echo $this->Page->renderInputElements();
        echo $this->Page->getFormButtons();
        echo $this->Form->end();
        ?>
    </div>
</div>
>>>>>>> 92ed69a6b777b6ce5d15390aea6b89cec6f2ec6c

$this->end();
?>
