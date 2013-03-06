<?php 
echo $this->Html->script('institution_site', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="additional" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Additional Info'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider')); ?>
	</h1>
	<?php
	echo $this->Form->create('InstitutionCustomValue', array(
		'url' => array(
			'controller' => 'Institutions',
			'action' => 'additionalEdit',
		)
	));
	?>
	<?php
	
	//pr($datafields); 
	//pr($datavalues);
	//pr($datavalues);
		$ctr = 1;
		foreach($datafields as $arrVals){
			if($arrVals['InstitutionCustomField']['type'] == 1){//Label
				
				 echo '<fieldset class="custom_section_break">
								<legend>'.$arrVals['InstitutionCustomField']['name'].'</legend>
						</fieldset>';
			   
			}elseif($arrVals['InstitutionCustomField']['type'] == 2) {//Text
				 echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionCustomField']['name'].'</div>
								<div class="field_value">'; 
				
								$val = (isset($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value']))?
									$datavalues[$arrVals['InstitutionCustomField']['id']][0]['value']:"";
								
								
								
								echo '<input type="text" class="default" name="data[InstitutionCustomValue][textbox]['.$arrVals["InstitutionCustomField"]["id"].'][value]" value="'.$val.'" >';
						  echo '</div>
						</div>';
			}elseif($arrVals['InstitutionCustomField']['type'] == 3) {//DropDown
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionCustomField']['name'].'</div>
								<div class="field_value">';
								   
									if(count($arrVals['InstitutionCustomFieldOption'])> 0){
										echo '<select name="data[InstitutionCustomValue][dropdown]['.$arrVals["InstitutionCustomField"]["id"].'][value]">';
										foreach($arrVals['InstitutionCustomFieldOption'] as $arrDropDownVal){
											if(isset($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'])){
												$defaults =  $datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'];
											}
											echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
										}
										echo '</select>';
									}
						  echo '</div>
						</div>';
				
				
				
			}elseif($arrVals['InstitutionCustomField']['type'] == 4) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionCustomField']['name'].'</div>
								<div class="field_value">';
									$defaults = array();
									 if(count($arrVals['InstitutionCustomFieldOption'])> 0){
										
										foreach($arrVals['InstitutionCustomFieldOption'] as $arrDropDownVal){
											
											if(isset($datavalues[$arrVals['InstitutionCustomField']['id']])){
												if(count($datavalues[$arrVals['InstitutionCustomField']['id']] > 0)){
													foreach($datavalues[$arrVals['InstitutionCustomField']['id']] as $arrCheckboxVal){
														$defaults[] = $arrCheckboxVal['value'];
													}
												}
												
											}
											echo '<input name="data[InstitutionCustomValue][checkbox]['.$arrVals["InstitutionCustomField"]["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';
											
										}
										
									}
									 
						  echo '</div>
						</div>';
			}elseif($arrVals['InstitutionCustomField']['type'] == 5) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionCustomField']['name'].'</div>
								<div class="field_value">';
								$val = '';
								if(isset($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'])){
									$val = ($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value']?$datavalues[$arrVals['InstitutionCustomField']['id']][0]['value']:""); 
								}
								
								echo '<textarea name="data[InstitutionCustomValue][textarea]['.$arrVals["InstitutionCustomField"]["id"].'][value]">'.$val.'</textarea>';
						  echo '</div>
						</div>';
			}
			
		}
	
	?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'additional'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
</div>