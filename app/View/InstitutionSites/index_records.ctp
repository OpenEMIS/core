<?php
$pageOptions = array('escape' => false, 'style' => 'display:none');
$pageNumberOptions = array('modulus' => 5, 'first' => 2, 'last' => 2, 'tag' => 'li', 'separator' => '', 'ellipsis' => '<li><span class="ellipsis">...</span></li>');
?>

<div class="row">
    <ul id="pagination">
        <?php echo $this->Paginator->prev(__('Previous'), null, null, $pageOptions); ?>
        <?php echo $this->Paginator->numbers($pageNumberOptions); ?>
        <?php echo $this->Paginator->next(__('Next'), null, null, $pageOptions); ?>
    </ul>
</div>
<table class="table table-striped table-hover table-bordered" action="InstitutionSites/index" total="<?php echo $this->Paginator->counter('{:count}'); ?>">
    <thead url="InstitutionSites/index">
        <tr>
            <td class="table_cell cell_code"><span class="left"><?php echo __('Code'); ?></span><span class="icon_sort_<?php echo ($sortedcol == 'InstitutionSite.code') ? $sorteddir : 'up'; ?>"  order="InstitutionSite.code"></span></td>
            <td class="table_cell cell_name"><span class="left"><?php echo __('Institution Name'); ?></span><span class="icon_sort_<?php echo ($sortedcol == 'InstitutionSite.name') ? $sorteddir : 'up'; ?>" order="InstitutionSite.name"></span></td>
        </tr></thead>
    <tbody>
        <?php
        if (isset($institutions) && count($institutions) > 0) {
            $ctr = 1;
            foreach ($institutions as $arrItems):
                //$area = (strlen($arrItems['Area']['name'])>14?substr($arrItems['Area']['name'], 0, 14).'...':$arrItems['Area']['name']);
                $id = $arrItems['InstitutionSite']['id'];
                $code = $this->Utility->highlight($searchField, $arrItems['InstitutionSite']['code']);
                $name = $this->Utility->highlight($searchField, '<b>' . $arrItems['InstitutionSite']['name'] . '</b>' . ((isset($arrItems['InstitutionSiteHistory']['name'])) ? '<br>' . $arrItems['InstitutionSiteHistory']['name'] : ''));
                ?>
                <tr row-id="<?php echo $id ?>" class="table_row_selection <?php echo ((($ctr++ % 2) != 0) ? 'odd' : 'even'); ?>">
                    <td class="table_cell"><?php echo $code; ?></td>
                    <td class="table_cell"><?php echo $this->Html->link($name, array('action' => 'view', $id), array('escape' => false)); ?></td>
                </tr>
            <?php
            endforeach;
        }
        ?>
    </tbody>
</table>
<?php if (sizeof($institutions) == 0) { ?>
    <div class="row center" style="color: red; margin-top: 15px;"><?php echo __('No Institution found.'); ?></div>
<?php } ?>
<div class="row">
    <ul id="pagination">
        <?php echo $this->Paginator->prev(__('Previous'), null, null, $pageOptions); ?>
        <?php echo $this->Paginator->numbers($pageNumberOptions); ?>
<?php echo $this->Paginator->next(__('Next'), null, null, $pageOptions); ?>
    </ul>
</div>