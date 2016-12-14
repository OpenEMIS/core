<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php
        $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
        $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    ?>

    <?php if (!empty($tableCells)) : ?>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                    <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
                </table>
            </div>
        </div>
    <?php else :
        echo __('No Special Need Types');
    ?>
    <?php endif ?>

<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <?php
        $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
        $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    ?>
    <?=
        $this->Form->input($ControllerAction['table']->alias().".special_need_type_id", [
            'label' => __('Add Special Need Type'),
            'type' => 'select',
            'options' => $attr['options'],
            'value' => 0,
            'onchange' => "$('#reload').val('AddExamCentreSpecialNeeds').click();"
        ]);
    ?>
    <div class="table-wrapper">
        <div class="table-responsive">
            <table class="table table-curved table-input">
                <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
            </table>
        </div>
    </div>
<?php endif ?>
