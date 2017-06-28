<?php
$this->extend('Page.Layout/container');

$this->start('contentBody');
?>

<div class="panel">
    <div class="panel-body">
        <?php
        echo $this->element('OpenEmis.alert');
        $formOptions = $this->ControllerAction->getFormOptions();
        $template = $this->Page->getFormTemplate();
        $this->Form->templates($template);
        echo $this->Form->create($data, $formOptions);
        echo $this->Page->renderInputElements();
        echo $this->Page->getFormButtons();
        echo $this->Form->end();
        ?>
    </div>
</div>

<?php $this->end() ?>
