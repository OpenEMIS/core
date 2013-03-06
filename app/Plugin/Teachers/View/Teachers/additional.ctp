<?php
// echo $this->Html->script('/Teachers/js/teachers', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper">
    <h1>
        <span><?php echo __('Additional Info'); ?></span>
        <?php echo $this->Html->link(__('Edit'), array('action' => 'additionalEdit'), array('class' => 'divider')); ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <?php
        // pr($datafields);
        // pr($datavalues);
    foreach($datafields as $arrVals){
                if($arrVals['TeacherCustomField']['type'] == 1){//Label
                    echo '<fieldset class="custom_section_break">
                    <legend>'.$arrVals['TeacherCustomField']['name'].'</legend>
                    </fieldset>';
                }elseif($arrVals['TeacherCustomField']['type'] == 2) {//Text
                    echo '<div class="custom_field">
                    <div class="field_label">'.$arrVals['TeacherCustomField']['name'].'</div>
                    <div class="field_value">';
                    if(isset($datavalues[$arrVals['TeacherCustomField']['id']][0]['value'])){
                        echo $datavalues[$arrVals['TeacherCustomField']['id']][0]['value'];
                    }
                    echo '</div>
                    </div>';
                }elseif($arrVals['TeacherCustomField']['type'] == 3) {//DropDown
                    echo '<div class="custom_field">
                    <div class="field_label">'.$arrVals['TeacherCustomField']['name'].'</div>
                    <div class="field_value">';
                                        /*
                                         if(count($arrVals['TeacherCustomFieldOption'])> 0){
                                            echo '<select>';
                                            foreach($arrVals['TeacherCustomFieldOption'] as $arrDropDownVal){

                                                if(isset($datavalues[$arrVals['TeacherCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['TeacherCustomField']['id']][0]['value'];
                                                }
                                                echo '<option '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';

                                            }
                                            echo '</select>';

                                        }
                                         */
                                        if(count($arrVals['TeacherCustomFieldOption'])> 0){
                                            $defaults = '';
                                            foreach($arrVals['TeacherCustomFieldOption'] as $arrDropDownVal){
                                                //pr($datavalues);
                                                //die;
                                                if(isset($datavalues[$arrVals['TeacherCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['TeacherCustomField']['id']][0]['value'];
                                                }
                                                echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
                                            }
                                        }
                                        echo '</div>
                                        </div>';



                                    }elseif($arrVals['TeacherCustomField']['type'] == 4) {
                                        echo '<div class="custom_field">
                                        <div class="field_label">'.$arrVals['TeacherCustomField']['name'].'</div>
                                        <div class="field_value">';
                                        $defaults = array();
                                        if(count($arrVals['TeacherCustomFieldOption'])> 0){

                                            foreach($arrVals['TeacherCustomFieldOption'] as $arrDropDownVal){

                                                if(isset($datavalues[$arrVals['TeacherCustomField']['id']])){
                                                    if(count($datavalues[$arrVals['TeacherCustomField']['id']] > 0)){
                                                        foreach($datavalues[$arrVals['TeacherCustomField']['id']] as $arrCheckboxVal){
                                                            $defaults[] = $arrCheckboxVal['value'];
                                                        }
                                                    }

                                                }
                                                echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';

                                            }

                                        }

                                        echo '</div>
                                        </div>';
                                    }elseif($arrVals['TeacherCustomField']['type'] == 5) {
                                        echo '<div class="custom_field">
                                        <div class="field_label">'.$arrVals['TeacherCustomField']['name'].'</div>
                                        <div class="field_value">';
                                        if(isset($datavalues[$arrVals['TeacherCustomField']['id']][0]['value'])){
                                            echo $datavalues[$arrVals['TeacherCustomField']['id']][0]['value'];
                                        }
                                        echo '</div>
                                        </div>';
                                    }

                                }

                                ?>

                            </div>