<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="health" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		if($_edit) {
			echo $this->Html->link(__('Add'), array('action' => 'health_medication_add'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/health_medication_view/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Name'); ?></div>
            <div class="table_cell"><?php echo __('Dosage'); ?></div>
            <div class="table_cell"><?php echo __('Commenced'); ?></div>
            <div class="table_cell"><?php echo __('Ended'); ?></div>
        </div>
       
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	
                <div class="table_cell"><?php echo $val[$modelName]['name']; ?></div>
             	<div class="table_cell"><?php echo $val[$modelName]['dosage'];?></div>
                <div class="table_cell"><?php echo $val[$modelName]['start_date'] ?></div>
                <div class="table_cell"><?php echo $val[$modelName]['end_date'] ?></div>
            </div>
           <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>