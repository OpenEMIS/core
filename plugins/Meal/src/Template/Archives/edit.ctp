<?php
/**
  * @var \App\View\AppView $this
  */
?>
<nav class="large-3 medium-4 columns" id="actions-sidebar">
    <ul class="side-nav">
        <li class="heading"><?= __('Actions') ?></li>
        <li><?= $this->Form->postLink(
                __('Delete'),
                ['action' => 'delete', $archive->id],
                ['confirm' => __('Are you sure you want to delete # {0}?', $archive->id)]
            )
        ?></li>
        <li><?= $this->Html->link(__('List Archives'), ['action' => 'index']) ?></li>
    </ul>
</nav>
<div class="archives form large-9 medium-8 columns content">
    <?= $this->Form->create($archive) ?>
    <fieldset>
        <legend><?= __('Edit Archive') ?></legend>
        <?php
            echo $this->Form->input('name');
            echo $this->Form->input('path');
            echo $this->Form->input('generated_on');
            echo $this->Form->input('generated_by');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
</div>
