<?php
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    $tableFooters = isset($attr['tableFooters']) ? $attr['tableFooters'] : [];
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="clearfix"></div>

    <div class="dropdown-filter">
        <div class="filter-label">
            <i class="fa fa-filter"></i>
            <label><?= __('Filter')?></label>
        </div>

        <?php
            $periodOptions = $attr['period_options'];
            $selectedPeriod = $attr['selected_period'];
        ?>
        <div class="select">
            <label><?= __('Competency Period') ?>:</label>
            <div class="input-select-wrapper">
                <select onchange="window.location.href = this.value">
                    <?php foreach ($periodOptions as $key => $value) { ?>
                        <option
                            value="<?= $this->Url->build($value['url']); ?>"
                            <?php if ($selectedPeriod == $key) { ?>
                                selected
                            <?php } ?>
                        ><?=__($value['name']);?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <?php
            $itemOptions = $attr['item_options'];
            $selectedItem = $attr['selected_item'];
        ?>
        <div class="select">
            <label><?= __('Competency Item') ?>:</label>
            <div class="input-select-wrapper">
                <select onchange="window.location.href = this.value">
                    <?php foreach ($itemOptions as $key => $value) { ?>
                        <option
                            value="<?= $this->Url->build($value['url']); ?>"
                            <?php if ($selectedItem == $key) { ?>
                                selected
                            <?php } ?>
                        ><?=__($value['name']);?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <?php
            $studentOptions = $attr['student_options'];
            $selectedStudent = $attr['selected_student'];
            $studentStatus = '';
            foreach ($studentOptions as $key => $value) {
                if ($selectedStudent == $key) { 
                    $studentStatus = $value['status'];
                }
            }
        ?>
        <div class="select">
            <label><?= __('Student') ?>:</label>
            <div class="input-select-wrapper">
                <select onchange="window.location.href = this.value">
                    <?php foreach ($studentOptions as $key => $value) { ?>
                        <option
                            value="<?= $this->Url->build($value['url']); ?>"
                            <?php if ($selectedStudent == $key) { ?>
                                selected
                            <?php } ?>
                        ><?=__($value['name']);?></option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="text">
            <label><?= __('Status') ?></label>
            <input  value="<?= __($studentStatus) ?>" disabled="disabled">
        </div>
    </div>
 
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table">
                <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
                <tfoot><?= $this->Html->tableCells($tableFooters) ?></tfoot>
            </table>
        </div>
    </div>
<?php endif ?>
