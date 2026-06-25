<?php
    $tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
    $tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
    <table class="table">
        <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
        <tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
    </table>
<?php endif ?>
