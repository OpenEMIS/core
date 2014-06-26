<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/OlapCube/js/olap', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader',__($subheader));
$this->start('contentActions');
$this->end();

$this->start('contentBody'); ?>

<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->params['action']), 'file');
echo $this->Form->create($model, $formOptions);
?>
	
    <?php
        echo $this->Form->input('cube_id', array(
            'options' => $cubeOptions,
            'default' => $selectedCubeOptions,
            'class' => 'form-control cube',
            'url' => sprintf('/%s/%s', $this->params['controller'], $this->params['action']),
            'onchange' => 'jsForm.change(this)'
        ));
    ?>
	<?php 
		echo $this->Form->input('row_id', array(
			'options' => $dimensionOptions,
			'class' => 'form-control rowCube',
			'default' => $selectedCubeRows,
			'url' => sprintf('/%s/%s/'.$selectedCubeOptions, $this->params['controller'], $this->params['action']),
			'onchange' => 'jsForm.change(this)'
			)); 
	?>
	<?php 
		echo $this->Form->input('column_id', array(
			'options' => $dimensionOptions,
			'class'=>'form-control columnCube',
			'default' => $selectedCubeColumns,
			'url' => sprintf('/%s/%s/'.$selectedCubeOptions.'/'.$selectedCubeRows, $this->params['controller'], $this->params['action']),
			'onchange' => 'jsForm.change(this)'
			)); 
	?>
	
	<?php 
		echo $this->Form->input('criteria_id', array(
			'empty' => __('--Select--'),
			'options' => $criteriaOptions,
			'class'=>'form-control criteria',
			'default' => $selectedCubeCriterias,
			'url' => sprintf('/%s/%s/'.$selectedCubeOptions.'/'.$selectedCubeRows.'/'.$selectedCubeColumns, $this->params['controller'], $this->params['action']),
			'onchange' => 'jsForm.change(this)'
			)); 
	?>
       
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
  
    <div class="controls view_controls">
       <input type="submit" value="<?php echo __("Generate"); ?>" class="btn_save btn_right"/>
        <?php echo $this->Html->link(__('Clear'), array('action' => 'olapReport'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
<?php echo $this->Form->end(); ?>

<?php $this->end(); ?>