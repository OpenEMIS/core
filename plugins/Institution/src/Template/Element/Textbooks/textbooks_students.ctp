<?php
    $tableClass = 'table-in-view';
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    $this->Form->unlockField('code');
    $this->Form->unlockField('textbook_status_id');
    $this->Form->unlockField('textbook_condition_id');
    $this->Form->unlockField('comment');
    $this->Form->unlockField('student_id');
?>

<?php if ($ControllerAction['action'] == 'edit' || $ControllerAction['action'] == 'add') : ?>
    <?php $tableClass = 'table-responsive'; ?>
    <div class="clearfix"></div>
    <?php
        echo $this->Form->input('<i class="fa fa-plus"></i> <span>'.__('Add Textbook').'</span>', [
            'label' => __('Action'),
            'type' => 'button',
            'class' => 'btn btn-default',
            'aria-expanded' => 'true',
            'onclick' => "$('#reload').val('addTextbooksStudents').click();"
        ]);
    ?>
    <div class="clearfix"></div>
    <hr>
<?php endif ?>

<div class="<?= $tableClass; ?>" autocomplete-ref="trainer_id">
    <table class="table">
        <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
        <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
    </table>
</div>
