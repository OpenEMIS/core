<?php 
echo $this->Html->script('institution_site', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="additional" class="content_wrapper edit">
	<h1>
		<span><?php echo __('More'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider')); ?>
	</h1>
	<?php
	echo $this->Form->create('InstitutionSiteCustomValue', array(
		'url' => array(
			'controller' => 'InstitutionSites',
			'action' => 'additionalEdit'
		)
	));
	?>
	<?php 
	//pr($datafields); 
	//pr($datavalues);
		$ctr = 1;
		foreach($datafields as $arrVals){
			if($arrVals['InstitutionSiteCustomField']['type'] == 1){//Label
				
				 echo '<fieldset class="custom_section_break">
							<legend>'.__($arrVals['InstitutionSiteCustomField']['name']).'</legend>
					   </fieldset>';
			   
			}elseif($arrVals['InstitutionSiteCustomField']['type'] == 2) {//Text
				 echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionSiteCustomField']['name'].'</div>
								<div class="field_value">'; 
								$val = (isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value']))?
									$datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value']:"";
								
								echo '<input type="text" class="default" name="data[InstitutionsSiteCustomFieldValue][textbox]['.$arrVals["InstitutionSiteCustomField"]["id"].'][value]" value="'.$val.'" >';
						  echo '</div>
						</div>';
			}elseif($arrVals['InstitutionSiteCustomField']['type'] == 3) {//DropDown
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionSiteCustomField']['name'].'</div>
								<div class="field_value">';
								   
									if(count($arrVals['InstitutionSiteCustomFieldOption'])> 0){
										echo '<select name="data[InstitutionsSiteCustomFieldValue][dropdown]['.$arrVals["InstitutionSiteCustomField"]["id"].'][value]">';
										foreach($arrVals['InstitutionSiteCustomFieldOption'] as $arrDropDownVal){
											if(isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'])){
												$defaults =  $datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'];
											}
											echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
										}
										echo '</select>';
									}
						  echo '</div>
						</div>';
				
				
				
			}elseif($arrVals['InstitutionSiteCustomField']['type'] == 4) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionSiteCustomField']['name'].'</div>
								<div class="field_value">';
									$defaults = array();
									 if(count($arrVals['InstitutionSiteCustomFieldOption'])> 0){
										
										foreach($arrVals['InstitutionSiteCustomFieldOption'] as $arrDropDownVal){
											
											if(isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']])){
												if(count($datavalues[$arrVals['InstitutionSiteCustomField']['id']] > 0)){
													foreach($datavalues[$arrVals['InstitutionSiteCustomField']['id']] as $arrCheckboxVal){
														$defaults[] = $arrCheckboxVal['value'];
													}
												}
												
											}
											echo '<input name="data[InstitutionsSiteCustomFieldValue][checkbox]['.$arrVals["InstitutionSiteCustomField"]["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';
											
										}
										
									}
									 
						  echo '</div>
						</div>';
			}elseif($arrVals['InstitutionSiteCustomField']['type'] == 5) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals['InstitutionSiteCustomField']['name'].'</div>
								<div class="field_value">';
								if(isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'])){
									$val = ($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value']?$datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value']:""); 
								}
								echo '<textarea name="data[InstitutionsSiteCustomFieldValue][textarea]['.$arrVals["InstitutionSiteCustomField"]["id"].'][value]">'.$val.'</textarea>';
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