<tr class="table_row <?php echo ($index+1)%2==0 ? 'li_even' : ''; ?>" row-id="<?php echo $index;?>">
	<td class="cell_description" style="width:60%">
		<?php echo $this->Form->input('DatawarehouseDimension.' . $index . '.id', array('id' => 'searchDimensionRow'.$index, 'div' => false, 
		'label' => false, 'options' => $datawarehouseDimensionOptions, 'empty'=>__('--Dimension--'), 'class' => 'form-control', 'onchange'=>
		'objDatawarehouse.populateByDimensionOption(this, \''.$index.'\',\''.$type.'\');','url'=>$this->params['controller']."/ajax_populate_by_dimension/")); ?>	
    </td>
	<td>
		<?php echo $this->Form->input('DatawarehouseDimension.' . $index . '.operator', array('div' => false, 
		'label' => false, 'options' => $operatorOptions, 'class' => 'form-control')); ?>	
    </td>
    <td>
		<?php echo $this->Form->input('DatawarehouseDimension.' . $index . '.value', array('div' => false, 
		'label' => false,  'options' => $valueOptions, 'class' => $type.$index.'DimensionValueOption form-control')); ?>	
    </td>
	<td class="cell_delete">
    	<span class="icon_delete" title="<?php echo __("Delete"); ?>" onclick="objDatawarehouse.deleteDimensionRow(this)"></span>
    </td>
</tr>