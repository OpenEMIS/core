<?php
    $tableClass = 'table-in-view';
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<div class="<?= $tableClass; ?>">
    <table class="table">
        <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
        <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
    </table>
</div>
