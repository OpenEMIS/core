<?php 
$typeOption = array('numerator', 'denominator');
foreach($typeOption as $type){ ?>
<div id="div<?php echo ucwords($type);?>" class="form-group">
	<div class="col-md-12">
	<fieldset class="section_group">
		<legend><?php echo __(ucwords($type)); ?></legend>
		<?php 
		$errorMsg = isset(${$type.'ErrorMsg'}) ? ${$type.'ErrorMsg'} : '';
		if(!empty($errorMsg)){ ?>
		<div class="row"><div class="alert alert_view alert_error" style="padding-left:15px;width:98%;"><?php echo __($errorMsg);?></div></div>
		<?php } ?>
		<div class="row">
		<?php if($editable){ ?>
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_module_id', array('empty'=>__('--Module--'), 'class'=>$type.'ModuleOption form-control', 'options'=>$datawarehouseModuleOptions, 'label'=>false, 'div'=>false, 'onchange'=>'objDatawarehouse.populateByModule(this, "'.$type.'");','url'=>$this->params['controller']."/ajax_populate_by_module/"));?>
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_operator', array('empty'=>__('--Operator--'),'class'=>$type.'OperatorOption form-control','options'=>${$type.'DatawarehouseOperatorFieldOptions'}, 'label'=>false, 'div'=>false, 'onchange'=>'objDatawarehouse.populateByModuleOperator(this, "'.$type.'");', 'url'=>$this->params['controller']."/ajax_populate_by_operator/"));?>
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_field', array('empty'=>__('--Field--'), 'class'=>$type.'FieldOption form-control', 'options'=>${$type.'DatawarehouseFieldOptions'}, 'label'=>false, 'div'=>false, 'onchange'=>'objDatawarehouse.populateByField(this, "'.$type.'");'));?>
		<?php echo $this->Form->input($model.'.'.$type.'_datawarehouse_field_id', array('class'=>$type.'FieldID', 'type'=> 'hidden')); ?>
		<?php }else{ ?>
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_module_id', array('type'=>'text', 'class'=>'form-control', 'label'=>false, 'div'=>'col-md-4'));?>
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_operator', array('type'=>'text','class'=>'form-control', 'label'=>false, 'div'=>'col-md-4'));?>
		<?php echo $this->Form->input('DatawarehouseField.'.$type.'_datawarehouse_field', array('type'=>'text', 'class'=>' form-control', 'label'=>false, 
		'div'=>'col-md-4'));?>
		<?php } ?>
		</div>
		<div class="row">
			<div class="table <?php echo $type;?>-dimension-row" style="padding-left:15px;width:98%;">
			<div class="delete-<?php echo $type;?>-dimension-row" name="data[Delete<?php echo ucwords($type);?>DimensionRow][{index}][id]"></div>
			<table class="table table-striped table-hover table-bordered table_body">
			<thead>
				<th><?php echo __('Dimension');?></th>
				<th><?php echo __('Operator');?></th>
				<th><?php echo __('Value');?></th>
				<?php if($editable){ ?>
				<th></th>
				<?php } ?>
			</thead>
			<tbody>
				<?php  
				if(isset($this->request->data[ucwords($type).'DatawarehouseDimension']) && !empty($this->request->data[ucwords($type).'DatawarehouseDimension'])){ ?>
					<?php   
						$i = 0; 
						foreach($this->request->data[ucwords($type).'DatawarehouseDimension'] as $key=>$val){?>
						<?php if(!empty($val['value']) && !empty($val['datawarehouse_dimension_id'])){ ?>
						<tr class="table_row " row-id="<?php echo $i;?>">
							<td class="table_cell cell_description" style="width:55%">
								<div class="input_wrapper">
								<?php echo $val['dimension_name'];?>
								<?php if(isset($val['id'])){ ?>
								<?php echo $this->Form->hidden(ucwords($type).'DatawarehouseDimension.' . $i . '.id', array('value'=>$val['id'], 
								'class' => 'control-id')); ?>
								<?php } ?>
								<?php echo $this->Form->hidden(ucwords($type).'DatawarehouseDimension.' . $i . '.datawarehouse_dimension_id', array('value'=>$val['datawarehouse_dimension_id'])); ?>
								<?php echo $this->Form->hidden(ucwords($type).'DatawarehouseDimension.' . $i . '.operator', array('value'=>$val['operator'])); ?>
								<?php echo $this->Form->hidden(ucwords($type).'DatawarehouseDimension.' . $i . '.value', array('value'=>$val['value'])); ?>
								<?php echo $this->Form->hidden(ucwords($type).'DatawarehouseDimension.' . $i . '.dimension_name', array('value'=>$val['dimension_name'])); ?>
								<?php if(isset($val['datawarehouse_indicator_id'])){ ?>
								<?php echo $this->Form->input(ucwords($type).'DatawarehouseDimension.' . $i . '.datawarehouse_indicator_id', array('type'=> 'hidden')); ?>
								<?php } ?>
								</div>
						    </td>
					 		<td>
						 		<div class="input_wrapper"><?php echo $val['operator'];?></div>
						 	</td>
						 	<td>
						 		<div class="input_wrapper"><?php echo $val['value'];?></div>
						 	</td>
						 	<?php if($editable){ ?>
					 		<td class="table_cell cell_delete">
						    	<span class="icon_delete" title="Delete" onclick="objDatawarehouse.deleteDimensionRow(this,'<?php echo $type;?>')"></span>
						    </td>
						    <?php } ?>
						</tr>
						<?php } ?>
				<?php 
					$i++;
				} ?>
				<?php } ?>
			</tbody>
			</table>
			</div>
			<?php if($editable){ ?>
			<div class="<?php echo $type;?>-add-dimension-row <?php echo !isset($this->request->data['DatawarehouseField'][$type.'_datawarehouse_module_id']) || 
				empty($this->request->data['DatawarehouseField'][$type.'_datawarehouse_module_id']) ? 'hide' : '';?>" style="padding-left:15px;width:98%;">
				<a class="void icon_plus" onclick="objDatawarehouse.addDimensionRow(this, '<?php echo $type;?>')" url="DataProcessing/ajax_add_dimension_row"  href="javascript: void(0)"><?php echo __('Add Dimension Row');?></a>
			</div>
			<?php } ?>
		</div>
	</fieldset>
	</div>
</div>
<?php } ?>