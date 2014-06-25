<?php if(!isset($ajax) || !$ajax) {
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('pagination', 'stylesheet', array('inline' => false));
echo $this->Html->css('search', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
if($_add) {
    echo $this->Html->link(__('Add'), array('action' => 'courseAdd'), array('class' => 'divider', 'id'=>'add'));
}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>
<?php echo $this->Form->create('Training', array('action' => 'search', 'id' => false)); ?>
<div class="row select_row form-group">
    <div class="col-md-4">
        <?php
            echo $this->Form->input('training_status_id', array(
                'options' => array_map('__',$statusOptions),
                'default' => $selectedStatus,
                'empty' => __('Current'),
                'class'=>'form-control',
                'label' => false,
                'url' => 'Training/course',
                'onchange' => 'jsForm.change(this)',
                'div' => false
            ));
        ?>
    </div>
</div>
<?php echo $this->Form->end(); ?>
<div id="mainlist">
<?php } ?>
<?php if(isset($data)) { ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
   <thead url="<?php echo $this->params['controller'];?>/course">
        <tr>
            <th>
                <span class="left"><?php echo __('Code'); ?></span>
                <span class="icon_sort_<?php echo ($sortedcol == 'TrainingCourse.code') ? $sorteddir : 'up'; ?>"  order="TrainingCourse.code"></span>
            </th>
            <th>
                <span class="left"><?php echo __('Title'); ?></span>
                <span class="icon_sort_<?php echo ($sortedcol == 'TrainingCourse.title') ? $sorteddir : 'up'; ?>" order="TrainingCourse.title"></span>
            </th>
            <th>
                <span class="left"><?php echo __('Credits'); ?></span>
                <span class="icon_sort_<?php echo ($sortedcol == 'TrainingCourse.credit_hours') ? $sorteddir : 'up'; ?>" order="TrainingCourse.credit_hours"></span>
            </th>
            <th>
                <span class="left"><?php echo __('Status'); ?></span>
                <span class="icon_sort_<?php echo ($sortedcol == 'TrainingStatus.name') ? $sorteddir : 'up'; ?>" order="TrainingStatus.name"></span>
            </th>
        </tr>
    </thead>
    <tbody>
    	<?php 
        if(!empty($data)){
        foreach($data as $id=>$val) { ?>
        <tr row-id="<?php echo $val[$modelName]['id']; ?>">
        	<td class="table_cell"><?php echo $val[$modelName]['code'] ?></td>
            <td class="table_cell"><?php echo $this->Html->link($val[$modelName]['title'], array('action' => 'courseView', $val[$modelName]['id']), array('escape' => false)); ?></td>
            <td class="table_cell"><?php echo  $val[$modelName]['credit_hours']; ?></td>
            <td class="table_cell"><?php echo $val['TrainingStatus']['name']; ?></td>
        </tr>
       <?php }
        }
        ?>
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
