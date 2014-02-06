<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training_need" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'trainingNeedAdd'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/trainingNeedView/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Code'); ?></div>
            <div class="table_cell"><?php echo __('Title'); ?></div>
            <div class="table_cell"><?php echo __('Credit'); ?></div>
            <div class="table_cell"><?php echo __('Status'); ?></div>
        </div>
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	<div class="table_cell"><?php echo $val['TrainingCourse']['code'] ?></div>
                <div class="table_cell"><?php echo $val['TrainingCourse']['title'] ?></div>
                <div class="table_cell"><?php echo $trainingCreditHourOptions[$val['TrainingCourse']['training_credit_hour_id']]; ?></div>
                <div class="table_cell"><?php echo  $val['TrainingStatus']['name']; ?></div>
                </div>
             </div>
           <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>