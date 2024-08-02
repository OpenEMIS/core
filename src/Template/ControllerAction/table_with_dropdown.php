<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php
        $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
        $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
        $tableFooters = isset($attr['tableFooters']) ? $attr['tableFooters'] : [];
    ?>

    <?php if (!empty($tableCells)) : ?>
        <div class="table-wrapper">
            <div class="table-in-view">
                <table class="table">
                    <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                    <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
                <?php if (!empty($tableFooters)) : ?>
                    <tfoot><?= $this->Html->tableCells($tableFooters) ?></tfoot>
                <?php endif ?>
                </table>
            </div>
        </div>
    <?php else :
        echo __('No record');
    ?>
    <?php endif ?>

<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <?php
        $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
        $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
        $tableFooters = isset($attr['tableFooters']) ? $attr['tableFooters'] : [];
        $label = $attr['label'];
        $inputField = implode('.', [$ControllerAction['table']->getAlias(), $attr['field']]);
        $inputEvent = 'Select' . str_replace(' ', '', $label);

        if (!isset($attr['options'])) {
            $attr['options'] = [];
        }

        $selectOptions = [];
        if (!empty($attr['options'])) {
            $selectOptions[] = '-- ' . __('Select ' . $label) . ' --';

            if (isset($attr['addAll']) && $attr['addAll'] && !empty($attr['options'])) {
                $selectOptions['-1'] = '-- ' . __('Add all ' . $label) . ' --';
            }

            $selectOptions += $attr['options'];
        } else {
            $selectOptions[] = $this->Label->get('general.select.noOptions');
        }

        $_inputOptions = [
            'type' => 'select',
            'label' => __('Add ' . $label),
            'options' => $selectOptions,
            'onchange' => "$('#reload').val('" . $inputEvent . "').click();"
        ];

        echo $this->Form->input($inputField, $_inputOptions);
    ?>
    <div class="table-responsive">
        <table class="table table-curved table-input">
            <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
            <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
        <?php if (!empty($tableFooters)) : ?>
            <tfoot><?= $this->Html->tableCells($tableFooters) ?></tfoot>
        <?php endif ?>
        </table>
    </div>
<?php endif ?>
