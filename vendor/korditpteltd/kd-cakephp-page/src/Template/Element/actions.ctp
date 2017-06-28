<?php
$url = ['plugin' => $this->request->params['plugin'], 'controller' => $this->request->params['controller']];
$view = $this->Page->getUrl(array_merge($url, ['action' => 'view', $entity->primaryKey]));
$edit = $this->Page->getUrl(array_merge($url, ['action' => 'edit', $entity->primaryKey]));
$delete = $this->Page->getUrl(array_merge($url, ['action' => 'delete', $entity->primaryKey]));
?>

<div class="dropdown">
    <button class="btn btn-dropdown action-toggle" type="button" id="action-menu" data-toggle="dropdown" aria-expanded="true">
        <?= __('Select') ?><span class="caret-down"></span>
    </button>

    <ul class="dropdown-menu action-dropdown" role="menu" aria-labelledby="action-menu">
        <div class="dropdown-arrow"><i class="fa fa-caret-up"></i></div>

        <li role="presentation"><a href="<?= $view ?>" role="menuitem" tabindex="-1"><i class="fa fa-eye"></i><?= __('View') ?></a></li>
        <li role="presentation"><a href="<?= $edit ?>" role="menuitem" tabindex="-1"><i class="fa fa-pencil"></i><?= __('Edit') ?></a></li>
        <li role="presentation"><a href="<?= $delete ?>" role="menuitem" tabindex="-1"><i class="fa fa-trash"></i><?= __('Delete') ?></a></li>
    </ul>
</div>
