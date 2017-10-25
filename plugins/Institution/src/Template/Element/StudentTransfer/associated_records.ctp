<?php
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<h3><?= __('Associated Records') ?></h3>
<div class="table-wrapper">
    <div class="table-responsive">
        <table class="table table-curved">
            <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
            <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
        </table>
    </div>
</div>
