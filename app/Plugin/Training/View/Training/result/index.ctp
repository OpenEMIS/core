<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
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
                'url' => 'Training/result',
                'onchange' => 'jsForm.change(this)'
            ));
        ?>
    </div>
</div>
<?php if(isset($data)) { ?>
<div class="table-responsive">
<table class="table table-striped table-hover table-bordered">
    <thead url="<?php echo $this->params['controller'];?>/resultView">
        <tr class="table_head">
       		<td class="table_cell"><?php echo __('Date'); ?></td>
            <td class="table_cell"><?php echo __('Location'); ?></td>
            <td class="table_cell"><?php echo __('Course'); ?></td>
            <td class="table_cell"><?php echo __('Status'); ?></td>
        </tr>
    </thead>
   
    <tbody>
    	<?php foreach($data as $id=>$val) { ?>
        <tr row-id="<?php echo $val[$modelName]['id']; ?>">
        	<td class="table_cell"><?php echo $val['TrainingSession']['start_date'] ?> - <?php echo $val['TrainingSession']['end_date'] ?></td>
            <td class="table_cell"><?php echo $val['TrainingSession']['location']; ?></td>
            <?php foreach($trainingCourses as $id2=>$val2){  
            if($val2['TrainingCourse']['id'] == $val['TrainingSession']['training_course_id']){ ?>
                <td class="table_cell">
                <?php echo $this->Html->link($val2['TrainingCourse']['code'] . ' - ' . $val2['TrainingCourse']['title'], array('action' => 'resultView', $val[$modelName]['id']), array('escape' => false)); ?>
                </td>
            <?php
                break;
                }
            }   
             ?>
            <td class="table_cell"><?php echo $val['TrainingStatus']['name'] ?></td>
        </tr>
       <?php } ?>
    </tbody>
</table>
</div>
<?php } ?>

<?php $this->end(); ?>  

