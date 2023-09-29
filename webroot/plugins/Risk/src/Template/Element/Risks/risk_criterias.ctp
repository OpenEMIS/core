<?php
    $tableClass = 'table-in-view';
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    $criteriaOptions = isset($attr['criteriaOptions']) ? $attr['criteriaOptions'] : [];
?>

<style>
    table .error-message-in-table {
        min-width: 100px;
        width: 100%;
    }
    table th label.table-header-label {
      background-color: transparent;
      border: medium none;
      margin: 0;
      padding: 0;
    }
</style>

<?php if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <?php $tableClass = 'table-responsive'; ?>
    <div class="clearfix"></div>
    <hr>
    <h3><?= __('Criterias') ?></h3>
    <?php
        $alias = $ControllerAction['table']->alias();
        // only when adding new indexes able to add criterias.
        if ($ControllerAction['action'] == 'add') {
            echo $this->Form->input("$alias.criteria_type", [
                'type' => 'select',
                'label' => __('Add Criteria'),
                'options' => $criteriaOptions,
                'onchange' => "$('#reload').val('addCriteria').click();"
            ]);
        }
    ?>
    <div class="<?= $tableClass; ?>" autocomplete-ref="indexes">
        <table class="table">
            <thead>
                <tr>
                    <th><?= __('Criteria') ?></th>
                    <th><?= __('Operator') ?></th>
                    <th class="required"><label class="table-header-label"><?= __('Threshold') ?></label></th>
                    <th class="required"><label class="table-header-label"><?= __('Risk') ?></label></th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
        </table>
    </div>
<?php endif ?>

<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="<?= $tableClass; ?>" autocomplete-ref="indexes">
        <table class="table">
            <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
            <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
        </table>
    </div>
<?php endif ?>



