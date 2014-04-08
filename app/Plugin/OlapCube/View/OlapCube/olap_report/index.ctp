<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/OlapCube/js/olap', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="olap_report" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'OlapCube', 'action' => 'olapReport', 'plugin'=>'OlapCube'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<div class="row">
        <div class="label"><?php echo __('Cube'); ?></div>
        <div class="value">
            <?php
                echo $this->Form->input('cube_id', array(
                    'options' => $cubeOptions,
                    'default' => $selectedCubeOptions,
                    'class' => 'cube',
                    'url' => sprintf('/%s/%s', $this->params['controller'], $this->params['action']),
                    'onchange' => 'jsForm.change(this)'
                ));
            ?>
        </div>
    </div>
	

	 <div class="row">
        <div class="label"><?php echo __('Row'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('row_id', array(
									'options' => $dimensionOptions,
								 	'class'=>'row',
									'onchange'=>'objOlapCube.getDetailsAfterChange(this)'
								 	));
		?>
        </div>
    </div>

     <div class="row">
        <div class="label"><?php echo __('Column'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('column_id', array(
									'options' => $dimensionOptions,
									'class'=>'column',
									'onchange'=>'objOlapCube.getDetailsAfterChange(this)'
									)); 
		?>
        </div>
    </div>

 	<div class="row">
        <div class="label"><?php echo __('Criteria'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('criteria_id', array(
									'empty' => __('--Select--'),
									'options' => $criteriaOptions,
									'class'=>'criteria',
									'url' => sprintf('/%s/%s/'.$selectedCubeOptions, $this->params['controller'], $this->params['action']),
                    				'onchange' => 'jsForm.change(this)'
									)); 
		?>
        </div>
    </div>
    <div class="row">
	 	<div class="label"><?php echo __('Criteria'); ?></div>
    	<div class="value">
    	<?php 
    		if(isset($fields) && !empty($fields)){
	    		foreach($fields as $field){
	    			echo $this->Form->checkbox('criteria_');
	    		}
	    	}
    	?>
    	</div>
    </div>
     <div class="controls">
        <input type="submit" value="<?php echo __("Generate"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();" target="_blank"/>
        <input type="reset" value="<?php echo __("Clear"); ?>" class="btn_cancel btn_left"/>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>