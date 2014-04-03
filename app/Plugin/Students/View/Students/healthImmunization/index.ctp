<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="health" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'healthImmunizationAdd'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/healthImmunizationView/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Date'); ?></div>
            <div class="table_cell"><?php echo __('Immunization'); ?></div>
            <div class="table_cell"><?php echo __('Dosage'); ?></div>
            <div class="table_cell"><?php echo __('Comment'); ?></div>
        </div>
       
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	<div class="table_cell"><?php echo $val[$modelName]['date'] ?></div>
                <div class="table_cell"><?php echo $healthImmunizationsOptions[$val[$modelName]['health_immunization_id']]; ?></div>
             	<div class="table_cell"><?php echo $val[$modelName]['dosage'];?></div>
                <div class="table_cell"><?php echo $val[$modelName]['comment'] ?>
                </div>
            </div>
           <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>