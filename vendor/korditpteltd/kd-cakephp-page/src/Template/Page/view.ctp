<?php
$this->extend('Page.Layout/container');

$this->start('toolbar');

echo $this->element('Page.button', ['title' => __('Back'), 'url' => ['action' => 'index'], 'iconClass' => 'fa kd-back', 'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']]);

if (array_key_exists('edit', $actions)) {
    echo $this->element('Page.button', ['title' => __('Edit'), 'url' => ['action' => 'edit', $data->primaryKey], 'iconClass' => 'fa kd-edit', 'linkOptions' => ['title' => __('Edit')]]);
}

if (array_key_exists('delete', $actions)) {
    echo $this->element('Page.button', ['title' => __('Delete'), 'url' => ['action' => 'delete', $data->primaryKey], 'iconClass' => 'fa kd-trash', 'linkOptions' => ['title' => __('Delete')]]);
}

$this->end();

$this->start('contentBody');
?>

<div class="panel">
    <div class="panel-body">
        <?php
        echo $this->element('OpenEmis.alert');
        echo $this->Page->renderViewElements();
        ?>
    </div>
</div>

<?php $this->end() ?>
