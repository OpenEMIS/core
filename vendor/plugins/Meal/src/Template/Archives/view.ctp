<?php
/**
  * @var \App\View\AppView $this
  */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Html->link(__('Edit Archive'), ['action' => 'edit', $archive->id]) ?> </li>
        <li><?= $this->Form->postLink(__('Delete Archive'), ['action' => 'delete', $archive->id], ['confirm' => __('Are you sure you want to delete # {0}?', $archive->id)]) ?> </li>
        <li><?= $this->Html->link(__('List Archives'), ['action' => 'index']) ?> </li>
        <li><?= $this->Html->link(__('New Archive'), ['action' => 'add']) ?> </li>
    </ul>
</nav>
<div class="archives view large-9 medium-8 columns content">
    <h3><?= h($archive->name) ?></h3>
    <table class="vertical-table">
        <tr>
            <th scope="row"><?= __('Name') ?></th>
            <td><?= h($archive->name) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Path') ?></th>
            <td><?= h($archive->path) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Generated By') ?></th>
            <td><?= h($archive->generated_by) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Id') ?></th>
            <td><?= $this->Number->format($archive->id) ?></td>
        </tr>
        <tr>
            <th scope="row"><?= __('Generated On') ?></th>
            <td><?= h($archive->generated_on) ?></td>
        </tr>
    </table>
</div>
