<?php 
echo $this->Html->script('institution_site', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper edit">
    <h1>
        <span><?php echo __('More'); ?></span>
        <?php if(!$WizardMode){ ?>
        <?php echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider')); ?>
        <?php } ?>
    </h1>
    <?php
    echo $this->Form->create('TeacherCustomValue', array(
        'url' => array('controller' => 'Teachers', 'action' => 'additionalEdit')
        ));
    ?>
    <?php 
        // pr($datafields); 
        // pr($datavalues);
    $ctr = 1;
    foreach($datafields as $arrVals){
        if($arrVals['TeacherCustomField']['type'] == 1){//Label

           echo '<fieldset class="custom_section_break">
           <legend>'.$arrVals['TeacherCustomField']['name'].'</legend>
           </fieldset>';
           
        }elseif($arrVals['TeacherCustomField']['type'] == 2) {//Text
           echo '<div class="custom_field">
           <div class="field_label">'.$arrVals['TeacherCustomField']['name'].'</div>
           <div class="field_value">'; 
           $val = (isset($datavalues[$arrVals['TeacherCustomField']['id']][0]['value']))?
           $datavalues[$arrVals['TeacherCustomField']['id']][0]['value']:"";

           echo '<input type="text" class="default" name="data[TeacherCustomValue][textbox]['.$arrVals["TeacherCustomField"]["id"].'][value]" value="'.$val.'" >';
           echo '</div>
           </div>';
        }elseif($arrVals['TeacherCustomField']['type'] == 3) {//DropDown
            echo '<div class="custom_field">
            <div class="field_label">'.$arrVals['TeacherCustomField']['name'].'</div>
            <div class="field_value">';
            
            // pr($arrVals);
            if(count($arrVals['TeacherCustomFieldOption'])> 0){
                echo '<select name="data[TeacherCustomValue][dropdown]['.$arrVals["TeacherCustomField"]["id"].'][value]">';
                foreach($arrVals['TeacherCustomFieldOption'] as $arrDropDownVal){
                    if(isset($datavalues[$arrVals['TeacherCustomField']['id']][0]['value'])){
                        $defaults =  $datavalues[$arrVals['TeacherCustomField']['id']][0]['value'];
                    }
                    echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
                }
                echo '</select>';
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
                    echo '<input name="data[TeacherCustomValue][checkbox]['.$arrVals["TeacherCustomField"]["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';

                }

            }

            echo '</div>
            </div>';
        }elseif($arrVals['TeacherCustomField']['type'] == 5) {
            echo '<div class="custom_field">
            <div class="field_label">'.$arrVals['TeacherCustomField']['name'].'</div>
            <div class="field_value">';
            if(isset($datavalues[$arrVals['TeacherCustomField']['id']][0]['value'])){
                $val = ($datavalues[$arrVals['TeacherCustomField']['id']][0]['value']?$datavalues[$arrVals['TeacherCustomField']['id']][0]['value']:""); 
            }
            echo '<textarea name="data[TeacherCustomValue][textarea]['.$arrVals["TeacherCustomField"]["id"].'][value]">'.$val.'</textarea>';
            echo '</div>
            </div>';
        }
        
    }

    ?>
    <div class="controls">
        <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" />
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'additional'), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
                if(!$mandatory){
                    echo $this->Form->hidden('nextLink', array('value'=>$nextLink)); 
                    if(!$wizardEnd){
                        echo $this->Form->submit('Skip', array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));
                    }
                } 
               if(!$wizardEnd){
                    echo $this->Form->submit('Next', array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
               }else{
                    echo $this->Form->submit('Finish', array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }
      } ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>