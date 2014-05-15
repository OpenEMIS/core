<?php
$session = $this->Session;
$sessModel = (stristr($customfields[0],"Institution") === TRUE)?'Institution':$customfields[0];
$sessVal = $session->read($sessModel.'.AdvancedSearch');

foreach ($customfields as $arrdataFieldsVal){
        
        if($arrdataFieldsVal == 'InstitutionSite'){
            echo '<div class="row">
                            <div class="label"> Site Type:</div>
                            <div class="value">
                            

                                            <div class="">
                                            <div class="field_value">
                                                    <select name="data[siteType]" onChange="objCustomFieldSearch.getDataFields($(this).val());">';
                                                    echo '   <option value="0">All</option>';
                                                    foreach($types as $key => $val){
                                                        echo '   <option value="'.$key.'" '.($key == $typeSelected?'selected="selected"':"").'>'.__($val).'</option>';
                                                    }
                                                    echo '</select>
                                            </div> 
                                            </div>
                               </div>
                    </div> </div>';
            
        }
        if(count(@$dataFields[$arrdataFieldsVal]) > 0){
                foreach ($dataFields[$arrdataFieldsVal] as $arrVals){
                    if($arrVals[$arrdataFieldsVal.'CustomField']['type'] == 1){//Label
                                              echo '<fieldset class="custom_section_break">
								<legend>'.__($arrVals[$arrdataFieldsVal.'CustomField']['name']).'</legend>
						</fieldset>';
                                       }else{
?>
                    <div class="row">
                            <div class="label"><?php echo __($arrVals[$arrdataFieldsVal.'CustomField']['name']); ?></div>
                            <div class="value">
                                <?php 
                                    if($arrVals[$arrdataFieldsVal.'CustomField']['type'] == 2) {//Text
                                                echo '<div class="">
                                                                               <div class="field_value">'; 
                                                
                                                
                                                                               $val = (isset($sessVal[$arrdataFieldsVal.'CustomValue']['textbox'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value']))?
                                                                                      $sessVal[$arrdataFieldsVal.'CustomValue']['textbox'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value']:"";
                                                                               echo '<input type="text" class="default" name="data['.$arrdataFieldsVal.'CustomValue'.'][textbox]['.$arrVals[$arrdataFieldsVal.'CustomField']["id"].'][value]" value="'.$val.'" >';
                                                                 echo '</div>
                                                               </div>';
                                       }elseif($arrVals[$arrdataFieldsVal.'CustomField']['type'] == 3) {//DropDown
                                               echo '<div class="">
                                                                               <div class="field_value">';
                                              
                                                                                        
                                                                                       if(count($arrVals[$arrdataFieldsVal.'CustomFieldOption'])> 0){
                                                                                           
                                                                                                $arrDropDownVal= array_unshift( $arrVals[$arrdataFieldsVal.'CustomFieldOption'], array("id"=>"","value"=>""));
                                                                                               echo '<select name="data['.$arrdataFieldsVal.'CustomValue][dropdown]['.$arrVals[$arrdataFieldsVal.'CustomField']["id"].'][value]">';
                                                                                               
                                                                                               foreach($arrVals[$arrdataFieldsVal.'CustomFieldOption'] as $arrDropDownVal){
                                                                                                   
                                                                                                       if(isset($sessVal[$arrdataFieldsVal.'CustomValue']['dropdown'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value'])){
                                                                                                               $defaults =  $sessVal[$arrdataFieldsVal.'CustomValue']['dropdown'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value'];
                                                                                                       }
                                                                                                       echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
                                                                                               }
                                                                                               echo '</select>';
                                                                                       }
                                                                 echo '</div>
                                                               </div>';



                                       }elseif($arrVals[$arrdataFieldsVal.'CustomField']['type'] == 4) {
                                               echo '<div class="">
                                                                               <div class="field_value">';
                                                                                       $defaults = array();
                                                                                        if(count($arrVals[$arrdataFieldsVal.'CustomFieldOption'])> 0){

                                                                                               foreach($arrVals[$arrdataFieldsVal.'CustomFieldOption'] as $arrDropDownVal){

                                                                                                       if(isset($sessVal[$arrdataFieldsVal.'CustomValue']['checkbox'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value'])){
                                                                                                           
                                                                                                               if(count($sessVal[$arrdataFieldsVal.'CustomValue']['checkbox'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value'] > 0)){
                                                                                                                       foreach($sessVal[$arrdataFieldsVal.'CustomValue']['checkbox'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value'] as $arrCheckboxVal){
                                                                                                                               $defaults[] = $arrCheckboxVal;
                                                                                                                       }
                                                                                                               }
                                                                                                               
                                                                                                       }
                                                                                                       echo '<input name="data['.$arrdataFieldsVal.'CustomValue][checkbox]['.$arrVals[$arrdataFieldsVal.'CustomField']["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';

                                                                                               }

                                                                                       }

                                                                 echo '</div> 
                                                               </div>';
                                       }elseif($arrVals[$arrdataFieldsVal.'CustomField']['type'] == 5) {
                                               echo '<div class=""> 
                                                                               <div class="field_value">';
                                                                               $val = '';
                                                                               if(isset($sessVal[$arrdataFieldsVal.'CustomValue']['textarea'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value'])){
                                                                                       $val = ($sessVal[$arrdataFieldsVal.'CustomValue']['textarea'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value']?$sessVal[$arrdataFieldsVal.'CustomValue']['textarea'][$arrVals[$arrdataFieldsVal.'CustomField']["id"]]['value']:""); 
                                                                               }

                                                                               echo '<textarea name="data['.$arrdataFieldsVal.'CustomValue][textarea]['.$arrVals[$arrdataFieldsVal.'CustomField']["id"].'][value]">'.$val.'</textarea>';
                                                                 echo '</div>
                                                               </div>';
                                       }

                                ?>
                            </div>
                    </div>
            <?php
                                       }}
            }
}