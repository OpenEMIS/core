<?php
// echo $this->Html->script('/Staff/js/staff', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper">
    <h1>
        <span><?php echo __('More'); ?></span>
        <?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'), array('class' => 'divider'));
		}
		echo $this->Html->link(__('Academic'), array('action' => 'custFieldYrView'), array('class' => 'divider'));
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <?php
        // pr($datafields);
        // pr($datavalues);
    foreach($datafields as $arrVals){
                if($arrVals['StaffCustomField']['type'] == 1){//Label
                    echo '<fieldset class="custom_section_break">
                    <legend>'.$arrVals['StaffCustomField']['name'].'</legend>
                    </fieldset>';
                }elseif($arrVals['StaffCustomField']['type'] == 2) {//Text
                    echo '<div class="custom_field">
                    <div class="field_label">'.$arrVals['StaffCustomField']['name'].'</div>
                    <div class="field_value">';
                    if(isset($datavalues[$arrVals['StaffCustomField']['id']][0]['value'])){
                        echo $datavalues[$arrVals['StaffCustomField']['id']][0]['value'];
                    }
                    echo '</div>
                    </div>';
                }elseif($arrVals['StaffCustomField']['type'] == 3) {//DropDown
                    echo '<div class="custom_field">
                    <div class="field_label">'.$arrVals['StaffCustomField']['name'].'</div>
                    <div class="field_value">';
                                        /*
                                         if(count($arrVals['StaffCustomFieldOption'])> 0){
                                            echo '<select>';
                                            foreach($arrVals['StaffCustomFieldOption'] as $arrDropDownVal){

                                                if(isset($datavalues[$arrVals['StaffCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['StaffCustomField']['id']][0]['value'];
                                                }
                                                echo '<option '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';

                                            }
                                            echo '</select>';

                                        }
                                         */
                                        if(count($arrVals['StaffCustomFieldOption'])> 0){
                                            $defaults = '';
                                            foreach($arrVals['StaffCustomFieldOption'] as $arrDropDownVal){
                                                //pr($datavalues);
                                                //die;
                                                if(isset($datavalues[$arrVals['StaffCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['StaffCustomField']['id']][0]['value'];
                                                }
                                                echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
                                            }
                                        }
                                        echo '</div>
                                        </div>';



                                    }elseif($arrVals['StaffCustomField']['type'] == 4) {
                                        echo '<div class="custom_field">
                                        <div class="field_label">'.$arrVals['StaffCustomField']['name'].'</div>
                                        <div class="field_value">';
                                        $defaults = array();
                                        if(count($arrVals['StaffCustomFieldOption'])> 0){

                                            foreach($arrVals['StaffCustomFieldOption'] as $arrDropDownVal){

                                                if(isset($datavalues[$arrVals['StaffCustomField']['id']])){
                                                    if(count($datavalues[$arrVals['StaffCustomField']['id']] > 0)){
                                                        foreach($datavalues[$arrVals['StaffCustomField']['id']] as $arrCheckboxVal){
                                                            $defaults[] = $arrCheckboxVal['value'];
                                                        }
                                                    }

                                                }
                                                echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';

                                            }

                                        }

                                        echo '</div>
                                        </div>';
                                    }elseif($arrVals['StaffCustomField']['type'] == 5) {
                                        echo '<div class="custom_field">
                                        <div class="field_label">'.$arrVals['StaffCustomField']['name'].'</div>
                                        <div class="field_value">';
                                        if(isset($datavalues[$arrVals['StaffCustomField']['id']][0]['value'])){
                                            echo $datavalues[$arrVals['StaffCustomField']['id']][0]['value'];
                                        }
                                        echo '</div>
                                        </div>';
                                    }

                                }

                                ?>

                            </div>
