<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="cell_description" style="width:60%">
		<?php echo $this->Form->input(ucwords($type).'DatawarehouseDimension.' . $index . '.datawarehouse_dimension_id', array('id' => 'search'.ucwords($type).'DimensionRow'.$index, 'div' => false, 
		'label' => false, 'options' => $datawarehouseDimensionOptions, 'empty'=>__('--Dimension--'), 'class' => 'form-control', 'onchange'=>
		'objDatawarehouse.populateByDimensionOption(this, \''.$index.'\',\''.$type.'\');','url'=>$this->params['controller']."/ajax_populate_by_dimension/")); ?>
		<?php echo $this->Form->hidden(ucwords($type).'DatawarehouseDimension.' . $index . '.dimension_name', array(
			'class'=> $type.$index.'DimensionOptionName')); ?>
    </td>
	<td>
		<?php echo $this->Form->input(ucwords($type).'DatawarehouseDimension.' . $index . '.operator', array('div' => false, 
		'label' => false, 'options' => $operatorOptions, 'class' => 'form-control')); ?>	
    </td>
    <td>
		<?php echo $this->Form->input(ucwords($type).'DatawarehouseDimension.' . $index . '.value', array('div' => false, 
		'label' => false,  'options' => $valueOptions, 'class' => $type.$index.'DimensionValueOption form-control'
		)); ?>	
    </td>
	<td class="cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objDatawarehouse.deleteDimensionRow(this,'<?php echo $type;?>');"></span>
    </td>
</tr>