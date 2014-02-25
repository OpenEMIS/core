<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('setup_variables', 'stylesheet', array('inline' => false));

echo $this->Html->script('setup_variables', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training_course" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'courseAdd'), array('class' => 'divider'));
		}
		?>
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
                    'url' => 'Training/course',
                    'onchange' => 'jsForm.change(this)'
                ));
            ?>
        </div>
    </div>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/courseView/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Code'); ?></div>
            <div class="table_cell"><?php echo __('Title'); ?></div>
            <div class="table_cell"><?php echo __('Credits'); ?></div>
            <div class="table_cell"><?php echo __('Status'); ?></div>
        </div>
       
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	<div class="table_cell"><?php echo $val[$modelName]['code'] ?></div>
                <div class="table_cell"><?php echo  $val[$modelName]['title']; ?></div>
                <div class="table_cell"><?php echo  $val[$modelName]['credit_hours']; ?></div>
                <div class="table_cell"><?php echo $val['TrainingStatus']['name'] ?>
                </div>
            </div>
           <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>