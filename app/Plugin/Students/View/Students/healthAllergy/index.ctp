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
			echo $this->Html->link(__('Add'), array('action' => 'healthAllergyAdd'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/healthAllergyView/">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('Type'); ?></div>
            <div class="table_cell"><?php echo __('Description'); ?></div>
            <div class="table_cell"><?php echo __('Severe'); ?></div>
            <div class="table_cell"><?php echo __('Comment'); ?></div>
        </div>
       
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	<div class="table_cell"><?php echo $healthAllergiesOptions[$val[$modelName]['health_allergy_type_id'] ]?></div>
                <div class="table_cell"><?php echo $val[$modelName]['description']; ?></div>
             	<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($val[$modelName]['severe'] == 1);?></div>
                <div class="table_cell"><?php echo $val[$modelName]['comment'] ?>
                </div>
            </div>
           <?php } ?>
        </div>
    </div>
    <?php } ?>
</div>