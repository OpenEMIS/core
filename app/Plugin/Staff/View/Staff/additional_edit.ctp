<?php 
echo $this->Html->script('institution_site', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper edit">
    <h1>
        <span><?php echo __('Additional Info'); ?></span>
        <?php echo $this->Html->link(__('Edit'), array('action' => 'additional'), array('class' => 'divider')); ?>
    </h1>
    <?php
    echo $this->Form->create('StaffCustomValue', array(
        'url' => array('controller' => 'Staff', 'action' => 'additionalEdit')
        ));
    ?>
    <?php 
        // pr($datafields); 
        // pr($datavalues);
    $ctr = 1;
    foreach($datafields as $arrVals){
        if($arrVals['StaffCustomField']['type'] == 1){//Label

           echo '<fieldset class="custom_section_break">
           <legend>'.$arrVals['StaffCustomField']['name'].'</legend>
           </fieldset>';
           
        }elseif($arrVals['StaffCustomField']['type'] == 2) {//Text
           echo '<div class="custom_field">
           <div class="field_label">'.$arrVals['StaffCustomField']['name'].'</div>
           <div class="field_value">'; 
           $val = (isset($datavalues[$arrVals['StaffCustomField']['id']][0]['value']))?
           $datavalues[$arrVals['StaffCustomField']['id']][0]['value']:"";

           echo '<input type="text" class="default" name="data[StaffCustomValue][textbox]['.$arrVals["StaffCustomField"]["id"].'][value]" value="'.$val.'" >';
           echo '</div>
           </div>';
        }elseif($arrVals['StaffCustomField']['type'] == 3) {//DropDown
            echo '<div class="custom_field">
            <div class="field_label">'.$arrVals['StaffCustomField']['name'].'</div>
            <div class="field_value">';
            
            // pr($arrVals);
            if(count($arrVals['StaffCustomFieldOption'])> 0){
                echo '<select name="data[StaffCustomValue][dropdown]['.$arrVals["StaffCustomField"]["id"].'][value]">';
                foreach($arrVals['StaffCustomFieldOption'] as $arrDropDownVal){
                    if(isset($datavalues[$arrVals['StaffCustomField']['id']][0]['value'])){
                        $defaults =  $datavalues[$arrVals['StaffCustomField']['id']][0]['value'];
                    }
                    echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
                }
                echo '</select>';
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
                    echo '<input name="data[StaffCustomValue][checkbox]['.$arrVals["StaffCustomField"]["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';

                }

            }

            echo '</div>
            </div>';
        }elseif($arrVals['StaffCustomField']['type'] == 5) {
            echo '<div class="custom_field">
            <div class="field_label">'.$arrVals['StaffCustomField']['name'].'</div>
            <div class="field_value">';
            $val = '';
            if(isset($datavalues[$arrVals['StaffCustomField']['id']][0]['value'])){
                $val = ($datavalues[$arrVals['StaffCustomField']['id']][0]['value']?$datavalues[$arrVals['StaffCustomField']['id']][0]['value']:""); 
            }
            echo '<textarea name="data[StaffCustomValue][textarea]['.$arrVals["StaffCustomField"]["id"].'][value]">'.$val.'</textarea>';
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
