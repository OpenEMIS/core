<?php if(!isset($ajax) || !$ajax) {
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'indicatorAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<div id="mainlist">
<?php } ?>
<?php if(isset($data)) { ?>
    <div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead url="<?php echo $this->params['controller'];?>/indicator">
            <tr>
                <th>
                    <span class="left"><?php echo __('Indicator'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'DatawarehouseIndicator.name') ? $sorteddir : 'up'; ?>"  order="DatawarehouseIndicator.name"></span>
                </th>
                <th>
                    <span class="left"><?php echo __('Unit'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'DatawarehouseIndicator.unit_id') ? $sorteddir : 'up'; ?>" order="DatawarehouseIndicator.unit_id"></span>
                </th>
                <th>
                    <span class="left"><?php echo __('Module'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'Module.name') ? $sorteddir : 'up'; ?>" order="Module.name"></span>
                </th>
                <th>
                    <span class="left"><?php echo __('Type'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'DatawarehouseIndicator.type') ? $sorteddir : 'up'; ?>" order="DatawarehouseIndicator.type"></span>
                </th>
            </tr>
       </thead>
        <tbody>
        	<?php foreach($data as $id=>$val) { ?>
            <tr row-id="<?php echo $val[$modelName]['id']; ?>">
            	<td class="table_cell"><?php echo $val[$modelName]['start_date'] ?> - <?php echo $val[$modelName]['end_date'] ?></td>
                <td class="table_cell"><?php echo $val[$modelName]['location']; ?></td>
                <td class="table_cell"><?php echo $this->Html->link($val['TrainingCourse']['code'] . ' - ' . $val['TrainingCourse']['title'], array('action' => 'sessionView', $val[$modelName]['id']), array('escape' => false)); ?></td>
                <td class="table_cell"><?php echo $val['TrainingStatus']['name']; ?></td>
            </tr>
           <?php } ?>
        </tbody>
    </table>
    </div>
<?php } ?>
<div class="row">
    <ul id="pagination">
        <?php echo $this->Paginator->prev(__('Previous'), null, null, $this->Utility->getPageOptions()); ?>
        <?php echo $this->Paginator->numbers($this->Utility->getPageNumberOptions()); ?>
        <?php echo $this->Paginator->next(__('Next'), null, null, $this->Utility->getPageOptions()); ?>
    </ul>
</div>
<?php if(!isset($ajax) || !$ajax) { ?>
</div>
<?php $this->end(); ?>  
<?php } ?>
