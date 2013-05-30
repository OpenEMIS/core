<?php
// echo $this->Html->script('/Students/js/students', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="additional" class="content_wrapper">
    <h1>
        <span><?php echo __('More'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'),	array('class' => 'divider')); 
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <?php
        // pr($datafields);
        // pr($datavalues);
    foreach($datafields as $arrVals){
                if($arrVals['StudentCustomField']['type'] == 1){//Label
                    echo '<fieldset class="custom_section_break">
                    <legend>'.$arrVals['StudentCustomField']['name'].'</legend>
                    </fieldset>';
                }elseif($arrVals['StudentCustomField']['type'] == 2) {//Text
                    echo '<div class="custom_field">
                    <div class="field_label">'.$arrVals['StudentCustomField']['name'].'</div>
                    <div class="field_value">';
                    if(isset($datavalues[$arrVals['StudentCustomField']['id']][0]['value'])){
                        echo $datavalues[$arrVals['StudentCustomField']['id']][0]['value'];
                    }
                    echo '</div>
                    </div>';
                }elseif($arrVals['StudentCustomField']['type'] == 3) {//DropDown
                    echo '<div class="custom_field">
                    <div class="field_label">'.$arrVals['StudentCustomField']['name'].'</div>
                    <div class="field_value">';
                                        /*
                                         if(count($arrVals['StudentCustomFieldOption'])> 0){
                                            echo '<select>';
                                            foreach($arrVals['StudentCustomFieldOption'] as $arrDropDownVal){

                                                if(isset($datavalues[$arrVals['StudentCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['StudentCustomField']['id']][0]['value'];
                                                }
                                                echo '<option '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';

                                            }
                                            echo '</select>';

                                        }
                                         */
                                        if(count($arrVals['StudentCustomFieldOption'])> 0){
                                            $defaults = '';
                                            foreach($arrVals['StudentCustomFieldOption'] as $arrDropDownVal){
                                                //pr($datavalues);
                                                //die;
                                                if(isset($datavalues[$arrVals['StudentCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['StudentCustomField']['id']][0]['value'];
                                                }
                                                echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
                                            }
                                        }
                                        echo '</div>
                                        </div>';



                                    }elseif($arrVals['StudentCustomField']['type'] == 4) {
                                        echo '<div class="custom_field">
                                        <div class="field_label">'.$arrVals['StudentCustomField']['name'].'</div>
                                        <div class="field_value">';
                                        $defaults = array();
                                        if(count($arrVals['StudentCustomFieldOption'])> 0){

                                            foreach($arrVals['StudentCustomFieldOption'] as $arrDropDownVal){

                                                if(isset($datavalues[$arrVals['StudentCustomField']['id']])){
                                                    if(count($datavalues[$arrVals['StudentCustomField']['id']] > 0)){
                                                        foreach($datavalues[$arrVals['StudentCustomField']['id']] as $arrCheckboxVal){
                                                            $defaults[] = $arrCheckboxVal['value'];
                                                        }
                                                    }

                                                }
                                                echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';

                                            }

                                        }

                                        echo '</div>
                                        </div>';
                                    }elseif($arrVals['StudentCustomField']['type'] == 5) {
                                        echo '<div class="custom_field">
                                        <div class="field_label">'.$arrVals['StudentCustomField']['name'].'</div>
                                        <div class="field_value">';
                                        if(isset($datavalues[$arrVals['StudentCustomField']['id']][0]['value'])){
                                            echo $datavalues[$arrVals['StudentCustomField']['id']][0]['value'];
                                        }
                                        echo '</div>
                                        </div>';
                                    }

                                }

                                ?>

                            </div>