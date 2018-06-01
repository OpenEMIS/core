<?php
echo $this->Html->script('Page.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);

$tableClass = 'table table-curved table-sortable table-checkable';
$displayReorder = !in_array('reorder', $disabledActions) && $data->count() > 1;
$displayAction = true;

if ($displayReorder) {
    echo $this->Html->script('Page.reorder', ['block' => true]);
    $action = ($this->request->param('action') == 'index') ? 'reorder' : $this->request->param('action');
    $baseUrl = $this->Page->getUrl(['action' => $action]);
}

$tableHeaders = $this->Page->getTableHeaders();
$tableData = $this->Page->getTableData();
?>

<div class="table-wrapper" ng-class="disableElement">
    <div class="table-responsive">
        <table class="<?= $tableClass ?>" <?= $displayReorder ? 'id="sortable" url="' . $baseUrl . '"' : '' ?>>
            <thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
            <tbody <?= $displayAction ? 'data-link="row"' : '' ?>><?php echo $this->Html->tableCells($tableData) ?></tbody>
        </table>
    </div>
</div>

<?php
$params = $this->Paginator->params();
$totalRecords = array_key_exists('count', $params) ? $params['count'] : 0;
?>

<?php if ($totalRecords > 0) : ?>
<div class="pagination-wrapper" ng-class="disableElement">
    <?php
    $totalPages = $params['pageCount'];

    if ($totalPages > 1) :
    ?>
    <ul class="pagination">
        <?php
        echo $this->Page->getPaginatorButtons('prev');
        echo $this->Page->getPaginatorNumbers();
        echo $this->Page->getPaginatorButtons('next');
        ?>
    </ul>
    <?php endif ?>
    <div class="counter">
        <?php
        $defaultLocale = $this->Page->locale();
        $this->Page->locale('en_US');
        ?>
        <?php
            $paginateCountString = $this->Paginator->counter([
                'format' => '{{start}} {{end}} {{count}}'
            ]);

            $paginateCountArray = explode(' ', $paginateCountString);
            $this->Page->locale($defaultLocale);
            echo sprintf(__('Showing %s to %s of %s records'), $paginateCountArray[0], $paginateCountArray[1], $paginateCountArray[2])
        ?>
    </div>
    <div class="display-limit">
        <span><?= __('Display') ?></span>
        <?= $this->Page->getLimitOptions() ?>
        <p><?= __('records') ?></p>
    </div>
</div>
<?php endif ?>
