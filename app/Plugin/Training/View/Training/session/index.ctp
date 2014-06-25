<?php if(!isset($ajax) || !$ajax) {
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'sessionAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<div class="row select_row form-group">
    <div class="col-md-4">
        <?php
            echo $this->Form->input('training_status_id', array(
                'options' => array_map('__',$statusOptions),
                'default' => $selectedStatus,
                'empty' => __('Current'),
                'class'=>'form-control',
                'label' => false,
                'url' => 'Training/session',
                'onchange' => 'jsForm.change(this)'
            ));
        ?>
    </div>
</div>
<div id="mainlist">
<?php } ?>
<?php if(isset($data)) { ?>
    <div class="table-responsive">
    <table class="table table-striped table-hover table-bordered">
        <thead url="<?php echo $this->params['controller'];?>/session">
            <tr>
                <th>
                    <span class="left"><?php echo __('Date'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'TrainingSession.start_date') ? $sorteddir : 'up'; ?>"  order="TrainingSession.start_date"></span>
                </th>
                <th>
                    <span class="left"><?php echo __('Location'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'TrainingSession.location') ? $sorteddir : 'up'; ?>" order="TrainingSession.location"></span>
                </th>
                <th>
                    <span class="left"><?php echo __('Course'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'TrainingCourse.code') ? $sorteddir : 'up'; ?>" order="TrainingCourse.code"></span>
                </th>
                <th>
                    <span class="left"><?php echo __('Status'); ?></span>
                    <span class="icon_sort_<?php echo ($sortedcol == 'TrainingStatus.name') ? $sorteddir : 'up'; ?>" order="TrainingStatus.name"></span>
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
