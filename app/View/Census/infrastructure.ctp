<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('infrastructure', false);
//pr($data);
?>

<?php echo $this->element('breadcrumb'); ?>
<input type="hidden" id="is_edit" value="false">
<div id="infrastructure" class="content_wrapper">
        <?php
	echo $this->Form->create('CensusInfrastructure', array(
			'id' => 'submitForm',
			'onsubmit' => 'return false',
			'inputDefaults' => array('label' => false, 'div' => false),	
			'url' => array('controller' => 'Census', 'action' => 'infrastructure')
		)
	);
	?>
	<h1>
		<span><?php echo __('Infrastructure'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'infrastructureEdit'), array('id' => 'edit-link', 'class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
				echo $this->Form->input('school_year_id', array(
					'id' => 'SchoolYearId',
					'options' => $years,
					'default' => $selectedYear
				));
			?>
		</div>
		<div style="float:right;">
		<ul class="legend">
			<li><span class="dataentry"></span><?php echo __('Data Entry'); ?></li>
			<li><span class="external"></span><?php echo __('External'); ?></li>
			<li><span class="estimate"></span><?php echo __('Estimate'); ?></li>
		</ul>
		</div>
	</div>
	
	<?php foreach($data as $infraname => $arrval) { $total = 0; ?>
	<fieldset class="section_group" id="<?php echo $infraname; ?>">
		<legend><?php echo ($infraname=='Sanitation'?__('Sanitation'):__($infraname));$infranameSing = __(Inflector::singularize($infraname));?></legend>
               
		<?php if(count(@$data[$infraname]['materials'])>0) {?>
		<?php if($infraname==='Buildings' || $infraname==='Sanitation') { ?>
                <select name="data[Census<?php echo $infraname; ?>][material]" id="<?php echo $infraname; ?>category" style="margin-bottom: 10px;">
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
				<div class="table_cell" style="white-space:normal"><?php echo $statVal; ?></div>
				<?php } ?>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>
			
			<div class="table_body" id="<?php echo $infraname; ?>_section">
				<?php foreach($arrval['types']  as $typeid => $typeVal) { ?>
				<div class="table_row">
					<div class="table_cell"><?php echo $typeVal; ?></div>
					
					<!-- Status -->
					<?php $statusTotal = 0; foreach($arrval['status'] as $statids => $statVal) { ?>
					
					<?php
						
						if($infraname==='Buildings'){
							//pr($data[$infraname]['data'][$typeid][$statids]);
							$val = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value'])?$data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['value']:'');
							$source = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'])?$data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']:'');
						
						}elseif($infraname==='Sanitation'){
							//echo $data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['id'];
							$val = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male'])?$data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['male']:'');
							$source = (isset($data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source'])?$data[$infraname]['data'][$typeid][$statids][key($arrval['materials'])]['source']:'');
						
						}else{
							$val = (isset($data[$infraname]['data'][$typeid][$statids]['value'])?$data[$infraname]['data'][$typeid][$statids]['value']:'');
							$source = (isset($data[$infraname]['data'][$typeid][$statids]['source'])?$data[$infraname]['data'][$typeid][$statids]['source']:'');
						}
						$statusTotal += $val;
						$record_tag="";
						switch ($source) {
							case 1:
								$record_tag.="row_external";break;
							case 2:
								$record_tag.="row_estimate";break;
						}
					?>
					
					<div class="table_cell cell_number <?php echo $record_tag; ?>">
						<?php echo $val; ?>
					</div>
					<?php } // end foreach(status) ?>
					<!-- Status -->
					
					<div class="table_cell cell_number"><?php echo $statusTotal>0 ? $statusTotal : ''; $total += $statusTotal; ?></div>
				</div>
				<?php } ?>
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
	
	<?php
		//pr($data);
	/*
		foreach($data as $infraname => $arrval){
			echo '<fieldset class="section_group" id="'.$infraname.'">
						<legend>'.$infraname.'</legend>';
							if($infraname == 'Buildings'){
								echo '<select style="margin-bottom:10px;" id="buildingcategory">';
								foreach ($data[$infraname]['materials'] as $key => $value) {
									echo '<option value="'.$key.'">'.$value.'</option>';
								}
								echo '</select> ';
								
							}
						echo '<div class="table">
								<div class="table_head">
										<div class="table_cell cell_category">Category</div>';
										foreach($arrval['status'] as $statVal){
											echo '<div class="table_cell">'.$statVal.'</div>';
											
										}
								   echo'
								</div>

								<div class="table_body" id="'.$infraname.'_section">';
								   foreach($arrval['types']  as $typeid => $typeVal){
										echo '<div class="table_row">
												<div class="table_cell">'.$typeVal.'</div>';
										foreach($arrval['status'] as $statids => $statVal){
											
											echo '<div class="table_cell">';
											if($infraname == 'Buildings'){
												echo (isset($data[$infraname]['data'][$typeid][$statids][1]['value'])?$data[$infraname]['data'][$typeid][$statids][1]['value']:'');
											}else{
												echo (isset($data[$infraname]['data'][$typeid][$statids]['value'])?$data[$infraname]['data'][$typeid][$statids]['value']:'');
											}
											
											echo '</div>';
											//echo '<div class="col_total">'. $statids.'</div>';
										}       
										echo '</div>';
									}
								
								  echo '
								</div>
						</div>
				</fieldset>';
		
		}
		*/
	?>
</div>