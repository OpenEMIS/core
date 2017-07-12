<?php
$this->extend('Page.Layout/container');

$primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

$this->start('toolbar');

echo $this->element('Page.button', ['title' => __('Back'), 'url' => ['action' => 'index'], 'iconClass' => 'fa kd-back', 'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']]);

if (array_key_exists('edit', $actions)) {
    echo $this->element('Page.button', ['title' => __('Edit'), 'url' => ['action' => 'edit', $primaryKey], 'iconClass' => 'fa kd-edit', 'linkOptions' => ['title' => __('Edit')]]);
}

if (array_key_exists('delete', $actions)) {
    echo $this->element('Page.button', ['title' => __('Delete'), 'url' => ['action' => 'delete', $primaryKey], 'iconClass' => 'fa kd-trash', 'linkOptions' => ['title' => __('Delete')]]);
}

$this->end();

$this->start('contentBody');

if (isset($elements)) {
    echo $this->Page->renderViewElements($elements);
} else {
    echo 'There are no elements';
}

$this->end();
?>
