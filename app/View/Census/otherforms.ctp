<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="grid" class="content_wrapper">
	<h1>
		<span><?php echo __('Other Forms'); ?></span>
		<?php
		if($_edit && $isEditable) {
			echo $this->Html->link(__('Edit'), array('action' => 'otherformsEdit', $selectedYear), array('class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'label' => false,
				'div' => false,
				'options' => $years,
				'default' => $selectedYear,
				'onchange' => 'Census.navigateYear(this)',
				'url' => 'Census/' . $this->action
			));
			?>
		</div>
	</div>

	<?php foreach($data as $arrval) { ?>
	<div class="custom_grid">
		<fieldset class="custom_section_break">
			<legend><?php echo $arrval['CensusGrid']['name']; ?></legend>
		</fieldset>
		
		<div class="desc"><?php echo $arrval['CensusGrid']['description']; ?></div>
		<div class="x_title"><?php echo $arrval['CensusGrid']['x_title']; ?></div>
		
		<div class="table_wrapper">
			<div class="table">
				<div class="table_head">
					<div class="table_cell y_col">&nbsp;</div>
					<?php foreach($arrval['CensusGridXCategory'] as $statVal) { ?>
					<div class="table_cell"><?php echo $statVal['name'] ?></div>
					<?php } ?>
				</div>
				
				<div class="table_body" id="<?php echo $statVal['name']; ?>_section">
				<?php
				$ctr = 1;
				foreach($arrval['CensusGridYCategory']  as $yCatId => $yCatName) {
				?>
					<div class="table_row<?php echo $ctr++%2==0? ' even' : ''; ?>">
						<div class="table_cell"><?php echo $yCatName['name']; ?></div>
						<?php foreach($arrval['CensusGridXCategory'] as $xCatId => $xCatName) { ?>
						<div class="table_cell">
							<?php 
							$val = isset($arrval['answer'][$xCatName['id']][$yCatName['id']]['value'])
								 ? $arrval['answer'][$xCatName['id']][$yCatName['id']]['value']
								 : ''; 
							
							echo $val;
							?>
						</div>
						<?php } ?>
					</div>
				<?php } ?>
				</div>
			</div>
		</div>
	</div>
	<?php }	?>
	
	<?php		
		foreach($datafields as $arrVals){
			if($arrVals['CensusCustomField']['type'] == 1){//Label
				echo '<fieldset class="custom_section_break">
							<legend>'.$arrVals['CensusCustomField']['name'].'</legend>
					  </fieldset>';
			}elseif($arrVals['CensusCustomField']['type'] == 2) {//Text
				echo '<div class="custom_field">
						<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
						<div class="field_value">';
						if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
							echo $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
						}
				echo '</div></div>';
			}elseif($arrVals['CensusCustomField']['type'] == 3) {//DropDown
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
								<div class="field_value">';
									/*
									 if(count($arrVals['CensusCustomFieldOption'])> 0){
										echo '<select>';
										foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
											
											if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
												$defaults =  $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
											}
											echo '<option '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
											
										}
										echo '</select>';
										
									}
									 */
									if(count($arrVals['CensusCustomFieldOption'])> 0){
										$defaults = '';
										foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
											//pr($datavalues);
											//die;
											if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
												$defaults =  $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
											}
											echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
										}
									}
						  echo '</div>
						</div>';
				
				
				
			}elseif($arrVals['CensusCustomField']['type'] == 4) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
								<div class="field_value">';
									$defaults = array();
									 if(count($arrVals['CensusCustomFieldOption'])> 0){
										
										foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
											
											if(isset($datavalues[$arrVals['CensusCustomField']['id']])){
												if(count($datavalues[$arrVals['CensusCustomField']['id']] > 0)){
													foreach($datavalues[$arrVals['CensusCustomField']['id']] as $arrCheckboxVal){
														$defaults[] = $arrCheckboxVal['value'];
													}
												}
												
											}
											echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';
											
										}
										
									}
									 
						  echo '</div>
						</div>';
			}elseif($arrVals['CensusCustomField']['type'] == 5) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
								<div class="field_value">';
								if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
									echo $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
								}
						  echo '</div>
						</div>';
			}
			
		}
	?>
</div>