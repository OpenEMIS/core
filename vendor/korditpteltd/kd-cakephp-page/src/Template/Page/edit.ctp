<?php
$this->extend('Page.Layout/container');

$primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

$this->start('toolbar');

echo $this->element('Page.button', ['url' => ['action' => 'view', $primaryKey], 'iconClass' => 'fa kd-back', 'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']]);
echo $this->element('Page.button', ['url' => ['action' => 'index'], 'iconClass' => 'fa kd-lists', 'linkOptions' => ['title' => __('List')]]);

$this->end();

$this->start('contentBody');
?>

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

<?php $this->end() ?>
