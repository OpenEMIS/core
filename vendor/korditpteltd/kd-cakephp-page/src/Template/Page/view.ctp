<?php
$this->extend('Page.Layout/container');

$primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

$this->start('toolbar');

echo $this->element('Page.button', ['title' => __('Back'), 'url' => ['action' => 'index'], 'iconClass' => 'fa kd-back', 'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']]);

if (!in_array('edit', $disabledActions)) {
    echo $this->element('Page.button', ['title' => __('Edit'), 'url' => ['action' => 'edit', $primaryKey], 'iconClass' => 'fa kd-edit', 'linkOptions' => ['title' => __('Edit')]]);
}

if (!in_array('delete', $disabledActions)) {
    echo $this->element('Page.button', ['title' => __('Delete'), 'url' => ['action' => 'delete', $primaryKey], 'iconClass' => 'fa kd-trash', 'linkOptions' => ['title' => __('Delete')]]);
}

$this->end();

$this->start('contentBody');

<<<<<<< HEAD
echo $this->Page->renderViewElements();
=======
if (isset($elements)) {
    echo $this->Page->renderViewElements($elements);
} else {
    echo 'There are no elements';
}
>>>>>>> 4a8bdb4502c14865c9dfd769ae7504224e300aef

$this->end();
?>
