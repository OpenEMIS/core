<script type="text/javascript">
    $(function() {
        jsTable.computeTotalForMoney('finalRating');
        var finalRating = $('.finalRating').html();
    });
</script>
<?php if ($ControllerAction['action'] == 'view') : ?>
    <?php
        $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
        $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
        $finalRating = isset($attr['finalRating']) ? $attr['finalRating'] : [];
    ?>
    <div class="table-wrapper">
        <div class="table-in-view">
            <table class="table">
                <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
                <tfoot>
                    <tr>
                        <td><?= __('Final Rating') ?></td>
                        <td><?=$finalRating?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <?php
        $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
        $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    ?>
    <div class="clearfix"></div>
    <label><?= __('Rating');?></label>
    <div class="input-form-wrapper">
        <div class="table-wrapper">
            <div class="table-responsive">
                <div class="table-in-view">
                    <table class="table">
                        <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
                        <tbody id="table_finalRating" computeType="finalRating"><?= $this->Html->tableCells($tableCells) ?></tbody>
                        <tfoot>
                            <tr>
                                <td><?= __('Final Rating') ?></td>
                                <td class="finalRating">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
