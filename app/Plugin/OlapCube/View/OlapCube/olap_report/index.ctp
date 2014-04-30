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
                    'class' => 'default cube',
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
									'class' => 'default rowCube',
									'default' => $selectedCubeRows,
									'url' => sprintf('/%s/%s/'.$selectedCubeOptions, $this->params['controller'], $this->params['action']),
                    				'onchange' => 'jsForm.change(this)'
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
									'class'=>'default columnCube',
									'default' => $selectedCubeColumns,
									'url' => sprintf('/%s/%s/'.$selectedCubeOptions.'/'.$selectedCubeRows, $this->params['controller'], $this->params['action']),
                    				'onchange' => 'jsForm.change(this)'
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
									'class'=>'default criteria',
									'default' => $selectedCubeCriterias,
									'url' => sprintf('/%s/%s/'.$selectedCubeOptions.'/'.$selectedCubeRows.'/'.$selectedCubeColumns, $this->params['controller'], $this->params['action']),
                    				'onchange' => 'jsForm.change(this)'
									)); 
		?>
        <p>
          	<?php 
    		if(isset($filterFields) && !empty($filterFields)){
	    		echo $this->Form->input('field', array(
				    'type' => 'select',
				    'multiple' => 'checkbox',
				    'options' => $filterFields,
				    'selected' => array_keys($filterFields)
				));
	    	}


    	?>
    	</p>
    	</div>
    </div>
  
     <div class="controls">
       <input type="submit" value="<?php echo __("Generate"); ?>" class="btn_save btn_right"/>
        <?php echo $this->Html->link(__('Clear'), array('action' => 'olapReport'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>