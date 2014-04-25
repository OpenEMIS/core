<?php 
//echo $this->Html->script('institution_site', false);
$this->extend('/Elements/layout/container');

$this->assign('contentHeader', __('More'));

$this->start('contentActions');
if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'),	array('class' => 'divider')); 
		}
$this->end();

$this->start('contentBody');
?>

<div id="site" class="content_wrapper">
        <?php 
        //pr($datafields); 
        //pr($datavalues);
            foreach($datafields as $arrVals){
                if($arrVals['InstitutionSiteCustomField']['type'] == 1){//Label
                    echo '<fieldset class="custom_section_break">
                                    <legend>'.__($arrVals['InstitutionSiteCustomField']['name']).'</legend>
                            </fieldset>';
                }elseif($arrVals['InstitutionSiteCustomField']['type'] == 2) {//Text
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['InstitutionSiteCustomField']['name'].'</div>
                                    <div class="field_value">';
                                    if(isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'])){
                                        echo $datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'];
                                    }
                              echo '</div>
                            </div>';
                }elseif($arrVals['InstitutionSiteCustomField']['type'] == 3) {//DropDown
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['InstitutionSiteCustomField']['name'].'</div>
                                    <div class="field_value">';
                                        /*
                                         if(count($arrVals['InstitutionSiteCustomFieldOption'])> 0){
                                            echo '<select>';
                                            foreach($arrVals['InstitutionSiteCustomFieldOption'] as $arrDropDownVal){
                                                
                                                if(isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'];
                                                }
                                                echo '<option '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
                                                
                                            }
                                            echo '</select>';
                                            
                                        }
                                         */
                                        if(count($arrVals['InstitutionSiteCustomFieldOption'])> 0){
                                            $defaults = '';
                                            foreach($arrVals['InstitutionSiteCustomFieldOption'] as $arrDropDownVal){
                                                //pr($datavalues);
                                                //die;
                                                if(isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'];
                                                }
                                                echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
                                            }
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
                                                echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';
                                                
                                            }
                                            
                                        }
                                         
                              echo '</div>
                            </div>';
                }elseif($arrVals['InstitutionSiteCustomField']['type'] == 5) {
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['InstitutionSiteCustomField']['name'].'</div>
                                    <div class="field_value">';
                                    if(isset($datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'])){
                                        echo $datavalues[$arrVals['InstitutionSiteCustomField']['id']][0]['value'];
                                    }
                              echo '</div>
                            </div>';
                }
                
            }
        
        ?>
	
</div>
<?php $this->end(); ?>