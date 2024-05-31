<?php
$url = ['plugin' => $this->request->params['plugin'], 'controller' => $this->request->params['controller']];

$actionItem = '<li role="presentation"><a href="%s" role="menuitem" tabindex="-1"><i class="%s"></i>%s</a></li>';
$primaryKey = !is_array($data) ? $data->primaryKey : $data['primaryKey']; // $data may be Entity or array

$view = true;
$edit = true;
$delete = true;

if (!is_array($data)) {
    if ($data->has('disabledActions')) {
        $view = !in_array('view', $data->disabledActions);
        $edit = !in_array('edit', $data->disabledActions);
        $delete = !in_array('delete', $data->disabledActions);
    }
} else {
    if (array_key_exists('disabledActions', $data)) {
        $view = !in_array('view', $data['disabledActions']);
        $edit = !in_array('edit', $data['disabledActions']);
        $delete = !in_array('delete', $data['disabledActions']);
    }
}

$rowActions = [];

if (!in_array('view', $disabledActions) && $view == true) {
    $rowActions[] = [
        'href' => $this->Page->getUrl(array_merge($url, ['action' => 'view', $primaryKey])),
        'icon' => 'fa fa-eye',
        'title' => __('View')
    ];
}

if (!in_array('edit', $disabledActions) && $edit == true) {
    $rowActions[] = [
        'href' => $this->Page->getUrl(array_merge($url, ['action' => 'edit', $primaryKey])),
        'icon' => 'fa fa-pencil',
        'title' => __('Edit')
    ];
}

if (!in_array('delete', $disabledActions) && $delete == true) {
    $rowActions[] = [
        'href' => $this->Page->getUrl(array_merge($url, ['action' => 'delete', $primaryKey])),
        'icon' => 'fa fa-trash',
        'title' => __('Delete')
    ];
}
?>

<div class="dropdown">
    <button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
        <?= __('Select') ?><span class="caret-down"></span>
    </button>

    <ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
        <div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>

        <?php
        foreach ($rowActions as $action) {
            echo sprintf($actionItem, $action['href'], $action['icon'], $action['title']);
        }
        ?>
    </ul>
</div>
