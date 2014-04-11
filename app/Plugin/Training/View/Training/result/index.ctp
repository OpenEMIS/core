<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training_session" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
    <div class="row select_row">
        <div class="label">
            <?php
                echo $this->Form->input('training_status_id', array(
                    'options' => $statusOptions,
                    'default' => $selectedStatus,
                    'empty' => __('All'),
                    'label' => false,
                    'url' => 'Training/result',
                    'onchange' => 'jsForm.change(this)'
                ));
            ?>
        </div>
    </div>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/resultView/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Date'); ?></div>
            <div class="table_cell"><?php echo __('Location'); ?></div>
            <div class="table_cell"><?php echo __('Course'); ?></div>
            <div class="table_cell"><?php echo __('Status'); ?></div>
        </div>
       
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	<div class="table_cell"><?php echo $val['TrainingSession']['start_date'] ?> - <?php echo $val['TrainingSession']['end_date'] ?></div>
                <div class="table_cell"><?php echo $val['TrainingSession']['location']; ?></div>
                <?php foreach($trainingCourses as $id2=>$val2){  
                if($val2['TrainingCourse']['id'] == $val['TrainingSession']['training_course_id']){ ?>
                    <div class="table_cell"><?php echo  $val2['TrainingCourse']['code'] . ' - ' . $val2['TrainingCourse']['title']; ?></div>
                <?php
                    break;
                    }
                }   

                 ?>
                <div class="table_cell"><?php echo $this->TrainingUtility->getTrainingStatus($modelName, $val['TrainingStatus']['name'], $val[$modelName]['training_status_id']); ?>
                </div>
            </div>
           <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>