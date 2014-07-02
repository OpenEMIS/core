<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('/OlapCube/css/olap', 'stylesheet', array('inline' => false));
echo $this->Html->script('/OlapCube/js/olap', false);


$this->extend('/Elements/layout/container');
$this->assign('contentHeader',__($subheader));
$this->start('contentActions');
$this->end();

$this->start('contentBody'); ?>
<div id="olap_report">
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
			echo '<div style="height: 200px; overflow-x: hidden;overflow-y: scroll;">';
    		echo $this->Form->input('field', array(
			    'type' => 'select',
			    'multiple' => 'checkbox',
			    'class' => 'filter-option',
			    'options' => $filterFields,
			    'selected' => array_keys($filterFields)
			));
			echo '</div>';
    	}
	?>
  
    <div class="controls view_controls">
       <input type="submit" value="<?php echo __("Generate"); ?>" class="btn_save btn_right" onclick="js:if(objOlapCube.checkValidate()){ return true; }else{ return false; }"/>
        <?php echo $this->Html->link(__('Clear'), array('action' => 'olapReport'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
<?php echo $this->Form->end(); ?>
</div>
<?php $this->end(); ?>