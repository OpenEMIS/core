<?php 
//echo $this->Html->script('institution_site', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="additional" class="content_wrapper">
	<h1>
		<span><?php echo __('Additional Info'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'),	array('class' => 'divider')); 
		}
		?>
	</h1>
        <?php 
        //pr($datafields); 
        //pr($datavalues);
            foreach($datafields as $arrVals){
                if($arrVals['InstitutionCustomField']['type'] == 1){//Label
                    echo '<fieldset class="custom_section_break">
                                    <legend>'.$arrVals['InstitutionCustomField']['name'].'</legend>
                            </fieldset>';
                }elseif($arrVals['InstitutionCustomField']['type'] == 2) {//Text
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['InstitutionCustomField']['name'].'</div>
                                    <div class="field_value">';
                                    if(isset($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'])){
                                        echo $datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'];
                                    }
                              echo '</div>
                            </div>';
                }elseif($arrVals['InstitutionCustomField']['type'] == 3) {//DropDown
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['InstitutionCustomField']['name'].'</div>
                                    <div class="field_value">';
                                        /*
                                         if(count($arrVals['InstitutionCustomFieldOption'])> 0){
                                            echo '<select>';
                                            foreach($arrVals['InstitutionCustomFieldOption'] as $arrDropDownVal){
                                                
                                                if(isset($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'];
                                                }
                                                echo '<option '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
                                                
                                            }
                                            echo '</select>';
                                            
                                        }
                                         */
                                        if(count($arrVals['InstitutionCustomFieldOption'])> 0){
                                            $defaults = '';
                                            foreach($arrVals['InstitutionCustomFieldOption'] as $arrDropDownVal){
                                                //pr($datavalues);
                                                //die;
                                                if(isset($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'];
                                                }
                                                echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
                                            }
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
                                                echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';
                                                
                                            }
                                            
                                        }
                                         
                              echo '</div>
                            </div>';
                }elseif($arrVals['InstitutionCustomField']['type'] == 5) {
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['InstitutionCustomField']['name'].'</div>
                                    <div class="field_value">';
                                    if(isset($datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'])){
                                        echo $datavalues[$arrVals['InstitutionCustomField']['id']][0]['value'];
                                    }
                              echo '</div>
                            </div>';
                }
                
            }
        
        ?>
	
</div>