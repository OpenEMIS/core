<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('infrastructure', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<input type="hidden" id="is_edit" value="true">
<div id="infrastructure" class="content_wrapper edit">
        <?php
	echo $this->Form->create('CensusInfrastructure', array(
			'id' => 'submitForm',
			//'onsubmit' => 'return false',
			'inputDefaults' => array('label' => false, 'div' => false),	
			'url' => array('controller' => 'Census', 'action' => 'infrastructureEdit')
		)
	);
	?>
	<h1>
		<span><?php echo __('Infrastructure'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'infrastructure', $selectedYear), array('class' => 'divider')); ?>
	</h1>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
				echo $this->Form->input('school_year_id', array(
					'options' => $years,
					'default' => $selectedYear,
					'onchange' => 'Census.navigateYear(this)',
					'url' => 'Census/' . $this->action
				));
			?>
		</div>
		<div class="row_item_legend">
		<ul class="legend">
			<li><span class="dataentry"></span><?php echo __('Data Entry'); ?></li>
			<li><span class="external"></span><?php echo __('External'); ?></li>
			<li><span class="estimate"></span><?php echo __('Estimate'); ?></li>
		</ul>
		</div>
	</div>
	<?php //pr($data);?>
	<?php foreach($data as $infraname => $arrval) { $total = 0; ?>
	<fieldset class="section_group" id="<?php echo $infraname; ?>">
		<legend><?php echo __($infraname);$infranameSing = __(Inflector::singularize($infraname));?></legend>
               
		<?php if(count(@$data[$infraname]['materials'])>0) {?>
		<?php if($infraname==='Buildings' || $infraname==='Sanitation') { ?>
                <select name="data[Census<?php echo $infranameSing; ?>][material]" id="<?php echo $infraname; ?>category" style="margin-bottom: 10px;">
			<?php foreach ($arrval['materials'] as $key => $value) { ?>
			<option value="<?php echo $key; ?>"><?php echo $value; ?></option>
			<?php } ?>
		</select>
		<?php } }?>
                <?php if($infraname==='Sanitation' ) { ?>
                <select name="data[Census<?php echo $infranameSing; ?>][gender]" id="SanitationGender" style="margin: 0 0 10px 10px;">
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="unisex" >Unisex</option>
                    
		</select>
		<?php } ?>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_category"><?php echo __('Category'); ?></div>
				<?php $statusCount = 0; foreach($arrval['status'] as $statVal) { $statusCount++; ?>
				<div class="table_cell"><?php echo $statVal; ?></div>
				<?php } ?>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>

			<div class="table_body" id="<?php echo $infraname; ?>_section">
				<?php $ctrModel = 1; foreach($arrval['types']  as $typeid => $typeVal) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $typeVal; ?></div>
					
					<!-- Status -->
					<?php $statusTotal = 0; foreach($arrval['status'] as $statids => $statVal) { ?>
					<div class="table_cell">
						<?php
						$modelName = Inflector::singularize($infraname);
						$inputName = 'data[Census'.$modelName.']['.$ctrModel.']';
						$infraId = 0;
						$infraVal = 0;
						$infraSource = 0;
						if($infraname === 'Buildings') { //got 3 dimension
							$infraId = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id'] : '';
							$infraVal = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value'] : '';
							$infraSource = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'] : '';
							
							?>
							<input type="hidden" name="<?php echo $inputName . '[infrastructure_material_id]'; ?>" value="<?php echo key($data[$infraname]['materials']); ?>">
							<input type="hidden" name="<?php echo $inputName . '[infrastructure_'.  rtrim(strtolower($infraname),"s").'_id]'; ?>" value="<?php echo $typeid; ?>">
						
							
						<?php }elseif($infraname==='Sanitation'){  
							
							$infraId = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id'] : '';
							$infraVal = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male'] : '';
							$infraSource = isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']) ? $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'] : '';
						
						?>	
							<input type="hidden" name="<?php echo $inputName . '[infrastructure_material_id]'; ?>" value="<?php echo key($data[$infraname]['materials']); ?>">
							<input type="hidden" name="<?php echo $inputName . '[infrastructure_'.  rtrim(strtolower($infraname),"s").'_id]'; ?>" value="<?php echo $typeid; ?>">
							
						<?php } else {
							$infraId = isset($data[$infraname]['data'][$typeid][$statids]['id']) ? $data[$infraname]['data'][$typeid][$statids]['id'] : '';
							$infraVal = isset($data[$infraname]['data'][$typeid][$statids]['value']) ? $data[$infraname]['data'][$typeid][$statids]['value'] : '';
							$infraSource = isset($data[$infraname]['data'][$typeid][$statids]['source']) ? $data[$infraname]['data'][$typeid][$statids]['source'] : '';
							
							?>
							<input type="hidden" name="<?php echo $inputName . '[infrastructure_'.  rtrim(strtolower($infraname),"s").'_id]'; ?>" value="<?php echo $typeid; ?>">
						
						<?php } // end if buildings
						$record_tag="";
						switch ($infraSource) {
							case 1:
								$record_tag.="row_external";break;
							case 2:
								$record_tag.="row_estimate";break;
						}
						$ctrModel++;
						$statusTotal += $infraVal;
						
						echo $this->Form->input('value', array(
								'type' => 'text',
								'class'=>$record_tag,
								'name' => $inputName . '[value]',
								'maxlength' => 8,
								'before' => '<div class="input_wrapper">',
								'after' => '</div>',
								'onkeyup' => 'Infrastructure.computeTotal(this)',
								'value' => $infraVal
							)
						);
						?>
						<?php if($infraId > 0 ){ ?>
						<input type="hidden" name="<?php echo $inputName . '[id]'; ?>" value="<?php echo $infraId; ?>">
						<?php } ?>
                                                <?php if($infraname === 'Buildings') {?>
						<input type="hidden" name="<?php echo $inputName . '[infrastructure_building_id]'; ?>" value="<?php echo $typeid; ?>">
                                                <?php } ?>
						<input type="hidden" name="<?php echo $inputName . '[infrastructure_status_id]'; ?>" value="<?php echo $statids; ?>">
					</div> <!-- end table_cell -->
				<?php } // end foreach(status) ?>
					<div class="table_cell cell_total cell_number"><?php echo $statusTotal>0 ? $statusTotal : ''; $total += $statusTotal; ?></div>
				</div>			
			<?php } // end foreach(types) ?>
			</div>
			
			<div class="table_foot">
				<?php for($i=0; $i<$statusCount; $i++) { ?>
				<div class="table_cell"></div>
				<?php } ?>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number"><?php echo $total; ?></div>
			</div>
		</div>
	</fieldset>
	<?php } ?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'infrastructure', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>
