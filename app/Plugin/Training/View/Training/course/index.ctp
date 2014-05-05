<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);

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
<div class="row select_row form-group">
    <div class="col-md-4">
        <?php
            echo $this->Form->input('training_status_id', array(
                'options' => $statusOptions,
                'default' => $selectedStatus,
                'empty' => __('All'),
                'class'=>'form-control',
                'label' => false,
                'url' => 'Training/course',
                'onchange' => 'jsForm.change(this)',
                'div' => false
            ));
        ?>
    </div>
</div>
<?php if(isset($data)) { ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
    <thead url="<?php echo $this->params['controller'];?>/courseView">
    <tr>
   		<td class="table_cell"><?php echo __('Code'); ?></td>
        <td class="table_cell"><?php echo __('Title'); ?></td>
        <td class="table_cell"><?php echo __('Credits'); ?></td>
        <td class="table_cell"><?php echo __('Status'); ?></td>
    </tr>
   </thead>
    <tbody>
    	<?php foreach($data as $id=>$val) { ?>
        <tr row-id="<?php echo $val[$modelName]['id']; ?>">
        	<td class="table_cell"><?php echo $val[$modelName]['code'] ?></td>
            <td class="table_cell"><?php echo $this->Html->link($val[$modelName]['title'], array('action' => 'courseView', $val[$modelName]['id']), array('escape' => false)); ?></td>
            <td class="table_cell"><?php echo  $val[$modelName]['credit_hours']; ?></td>
            <td class="table_cell"><?php echo $val['TrainingStatus']['name'] ?></td>
        </tr>
       <?php } ?>
    </tbody>
</table>
</div>
<?php } ?>

<?php $this->end(); ?>  
