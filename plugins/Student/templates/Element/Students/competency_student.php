<?php
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
    $tableFooters = isset($attr['tableFooters']) ? $attr['tableFooters'] : [];
    $c = count($tableHeaders);
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
    <div class="clearfix"></div>
    <div class="table-wrapper">
        <div class="table-in-view">
            <?php for ($i=0;$i<$c;$i++) {?>
            <table class="table">
                <thead><?= $this->Html->tableHeaders($tableHeaders[$i]) ?></thead>
                <tbody><?= $this->Html->tableCells($tableCells[$i]) ?></tbody>
                <tfoot><?= $this->Html->tableCells($tableFooters[$i]) ?></tfoot>
            </table>
        <?php }?>
        </div>
    </div>
<?php endif ?>
