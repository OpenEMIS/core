<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('More'));

$this->start('contentActions');
if(!$WizardMode) {
	echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$customElement = array(
	1 => 'customFields/label',
	2 => 'customFields/text',
	3 => 'customFields/dropdown',
	4 => 'customFields/multiple',
	5 => 'customFields/textarea'
);
$model = 'StudentCustomField';
$modelOption = 'StudentCustomFieldOption';
$modelValue = 'StudentCustomValue';
$action = 'edit';
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'additionalEdit'));
unset($formOptions['div']);
unset($formOptions['label']);
echo $this->Form->create($modelValue, $formOptions);

foreach($data as $obj) {
	$element = $customElement[$obj[$model]['type']];
	echo $this->element($element, compact('model', 'modelOption', 'modelValue', 'obj', 'action'));
}
echo $this->Form->end();
$this->end();
?>


<?php echo $this->element('breadcrumb'); ?>

<div id="additional" class="content_wrapper edit">
    <h1>
        <span><?php echo __('More'); ?></span>
         <?php if(!$WizardMode){ ?>
        <?php echo $this->Html->link(__('View'), array('action' => 'additional'), array('class' => 'divider')); ?>
        <?php } ?>
    </h1>
    <?php echo $this->element('alert'); ?>
    <?php
    echo $this->Form->create('StudentCustomValue', array(
        'url' => array('controller' => 'Students', 'action' => 'additionalEdit')
	));
    ?>
    <?php 
	
    $ctr = 1;
    foreach($datafields as $arrVals){
        if($arrVals['StudentCustomField']['type'] == 1){//Label

           echo '<fieldset class="custom_section_break">
           <legend>'.$arrVals['StudentCustomField']['name'].'</legend>
           </fieldset>';
           
        }elseif($arrVals['StudentCustomField']['type'] == 2) {//Text
           echo '<div class="custom_field">
           <div class="field_label">'.$arrVals['StudentCustomField']['name'].'</div>
           <div class="field_value">'; 
           $val = (isset($datavalues[$arrVals['StudentCustomField']['id']][0]['value']))?
           $datavalues[$arrVals['StudentCustomField']['id']][0]['value']:"";

           echo '<input type="text" class="default" name="data[StudentCustomValue][textbox]['.$arrVals["StudentCustomField"]["id"].'][value]" value="'.$val.'" >';
           echo '</div>
           </div>';
        }elseif($arrVals['StudentCustomField']['type'] == 3) {//DropDown
            echo '<div class="custom_field">
            <div class="field_label">'.$arrVals['StudentCustomField']['name'].'</div>
            <div class="field_value">';
            
            // pr($arrVals);
            if(count($arrVals['StudentCustomFieldOption'])> 0){
                echo '<select name="data[StudentCustomValue][dropdown]['.$arrVals["StudentCustomField"]["id"].'][value]">';
                foreach($arrVals['StudentCustomFieldOption'] as $arrDropDownVal){
                    if(isset($datavalues[$arrVals['StudentCustomField']['id']][0]['value'])){
                        $defaults =  $datavalues[$arrVals['StudentCustomField']['id']][0]['value'];
                    }
                    echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
                }
                echo '</select>';
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
                    echo '<input name="data[StudentCustomValue][checkbox]['.$arrVals["StudentCustomField"]["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';

                }

            }

            echo '</div>
            </div>';
        }elseif($arrVals['StudentCustomField']['type'] == 5) {
            echo '<div class="custom_field">
            <div class="field_label">'.$arrVals['StudentCustomField']['name'].'</div>
            <div class="field_value">';
            if(isset($datavalues[$arrVals['StudentCustomField']['id']][0]['value'])){
                $val = ($datavalues[$arrVals['StudentCustomField']['id']][0]['value']?$datavalues[$arrVals['StudentCustomField']['id']][0]['value']:""); 
            }
            echo '<textarea name="data[StudentCustomValue][textarea]['.$arrVals["StudentCustomField"]["id"].'][value]">'.$val.'</textarea>';
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
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));

                if(!$wizardEnd){
                    echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }else{
                    echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }
                if($mandatory!='1' && !$wizardEnd){
                    echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
                } 
      } ?>
    </div>
    <?php echo $this->Form->end(); ?>
</div>