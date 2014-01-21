<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>
<div id="attendance" class="content_wrapper">
    <h1>
        <span><?php echo __($subheader); ?></span>
        <?php
		if($_edit) {
			echo $this->Html->link(__('Add'), array('action' => 'healthFamilyAdd'), array('class' => 'divider'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php if(isset($data)) { ?>
    <div class="table allow_hover full_width" action="<?php echo $this->params['controller'];?>/healthFamilyView/">
        <div class="table_head">
        	<div class="table_cell"><?php echo __('Relationship'); ?></div>
            <div class="table_cell"><?php echo __('Conditions'); ?></div>
            <div class="table_cell"><?php echo __('Current'); ?></div>
            <div class="table_cell"><?php echo __('Comment'); ?></div>
        </div>
        
       
        <div class="table_body">
        	<?php foreach($data as $id=>$val) { ?>
            <div class="table_row" row-id="<?php echo $val[$modelName]['id']; ?>">
            	<div class="table_cell"><?php echo $healthRelationshipsOptions[$val[$modelName]['health_relationship_id']]; ?></div>
                <div class="table_cell"><?php echo $healthConditionsOptions[$val[$modelName]['health_condition_id']]; ?></div>
             	<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($val[$modelName]['current']==1);?></div>
                <div class="table_cell"><?php echo $val[$modelName]['comment'] ?>
                </div>
            </div>
             <?php } ?>
        </div>
       
    </div>
    <?php } ?>
</div>