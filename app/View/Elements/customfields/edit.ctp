<?php 
echo $this->Html->script('custom_field', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Academic'));

$this->start('contentActions');
if($_edit) {
			echo $this->Html->link(__('View'), array('action' => rtrim($this->action,'Edit').'View',$id),	array('class' => 'divider','onClick'=>'return custom.view.redirect(this)')); 
		}
$this->end();

$this->start('contentBody');
?>

<div id="additional" class="content_wrapper edit">
	<?php 
	
	if(count(@$dataFields)) {
	?>
	<div class="row year"  style="margin-left:5px;">
		<div class="col-md-2"><?php echo __('Year'); ?></div>
		<div class="col-md-3">
			<?php
				echo $this->Form->input('school_year_id', array(
					'label' => false,
					'div' => false,
					'class' => 'form-control',
					'options' => $years,
					'default' => $selectedYear,
					'onchange' => 'custom.view.changeCategory(this)',
					'url' => $this->params['controller']. '/' . $this->action .'/'.$id 
				));
			?>
		</div>
	</div>
	
	<?php
	
	
	
	
	echo $this->Form->create($arrMap['CustomValue']);
	?>
	<?php 
	//pr($datafields); 
	//pr($dataValues);
		$ctr = 1;
		foreach($dataFields as $arrVals){
			if($arrVals[$arrMap['CustomField']]['type'] == 1){//Label
				
				 echo '<fieldset class="section_break">
							<legend>'.__($arrVals[$arrMap['CustomField']]['name']).'</legend>
					   </fieldset>';
			   
			}elseif($arrVals[$arrMap['CustomField']]['type'] == 2) {//Text
				 echo '<div class="custom_field">
								<div class="field_label">'.$arrVals[$arrMap['CustomField']]['name'].'</div>
								<div class="field_value">'; 
								$val = (isset($dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value']))?
									$dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value']:"";
								
								echo '<input type="text" class="default form-control" name="data['.$arrMap['CustomValue'].'][textbox]['.$arrVals[$arrMap['CustomField']]["id"].'][value]" value="'.$val.'" >';
						  echo '</div>
						</div>';
			}elseif($arrVals[$arrMap['CustomField']]['type'] == 3) {//DropDown
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals[$arrMap['CustomField']]['name'].'</div>
								<div class="field_value">';
								   
									if(count($arrVals[$arrMap['CustomFieldOption']])> 0){
										echo '<select name="data['.$arrMap['CustomValue'].'][dropdown]['.$arrVals[$arrMap['CustomField']]["id"].'][value]" class="form-control">';
										foreach($arrVals[$arrMap['CustomFieldOption']] as $arrDropDownVal){
											if(isset($dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'])){
												$defaults =  $dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'];
											}
											echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
										}
										echo '</select>';
									}
						  echo '</div>
						</div>';
				
				
				
			}elseif($arrVals[$arrMap['CustomField']]['type'] == 4) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals[$arrMap['CustomField']]['name'].'</div>
								<div class="field_value">';
									$defaults = array();
									 if(count($arrVals[$arrMap['CustomFieldOption']])> 0){
										
										foreach($arrVals[$arrMap['CustomFieldOption']] as $arrDropDownVal){
											
											if(isset($dataValues[$arrVals[$arrMap['CustomField']]['id']])){
												if(count($dataValues[$arrVals[$arrMap['CustomField']]['id']] > 0)){
													foreach($dataValues[$arrVals[$arrMap['CustomField']]['id']] as $arrCheckboxVal){
														$defaults[] = $arrCheckboxVal['value'];
													}
												}
												
											}
											echo '<input name="data['.$arrMap['CustomValue'].'][checkbox]['.$arrVals[$arrMap['CustomField']]["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';
											
										}
										
									}
									 
						  echo '</div>
						</div>';
			}elseif($arrVals[$arrMap['CustomField']]['type'] == 5) {
				echo '<div class="custom_field">
								<div class="field_label">'.$arrVals[$arrMap['CustomField']]['name'].'</div>
								<div class="field_value">';
								if(isset($dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'])){
									$val = ($dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value']?$dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value']:""); 
								}
								echo '<textarea name="data['.$arrMap['CustomValue'].'][textarea]['.$arrVals[$arrMap['CustomField']]["id"].'][value]" class="form-control">'.@$val.'</textarea>';
						  echo '</div>
						</div>';
			}
			
		}
	
	?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php 
		echo $this->Html->link(__('Cancel'), array('action' => rtrim($this->action,'Edit').'View',$id),	array('class' => 'btn_cancel btn_left','onClick'=>'return custom.view.redirect(this)')); 
		?>
		
	</div>
	<?php echo $this->Form->end(); 
	} ?>
</div>
<?php 	$this->end();?>