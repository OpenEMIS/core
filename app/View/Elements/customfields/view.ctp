<?php 
echo $this->Html->script('custom_field', false);
//echo $this->Html->css('census', 'stylesheet', array('inline' => false));

?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper">
	<h1>
		<span><?php echo __('Academic'); ?></span>
		<?php
			if(@$displayEdit){
				if($_edit) {
					echo $this->Html->link(__('Edit'), array('action' => rtrim($this->action,'View').'Edit',$id),	array('class' => 'divider','onClick'=>'return custom.view.redirect(this)')); 
				}
			}
		
		?>
	</h1>
	<div class="row year" style="margin-left:5px;">
		<div class="label" style="width: 90px;"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
				echo $this->Form->input('school_year_id', array(
					'label' => false,
					'div' => false,
					'options' => $years,
					'default' => $selectedYear,
					'onchange' => 'custom.view.changeCategory(this)',
					'url' => $this->params['controller']. '/' . $this->action .'/'.$id 
				));
			?>
		</div>
	</div>
	<?php if(isset($institution_sites)) { ?>
	<div class="row" style="margin-left:5px;">
		<div class="label" style="width: 90px;"><?php echo __('Institution Sites'); ?></div>
		<div class="value" style="margin-bottom: 10px;">
			<?php
				echo $this->Form->input('institution_site_id', array(
					'label' => false,
					'div' => false,
					'options' => $institution_sites,
					'default' => $siteid,
					'onchange' => 'custom.view.changeCategory(this,true)',
					'url' => $this->params['controller']. '/' . $this->action .'/'.$id 
				));
			?>
		</div>
	</div>
	<?php } ?>
    <?php
		if(count(@$dataFields) > 0 ) {
            foreach($dataFields as $arrVals){
                if($arrVals[$arrMap['CustomField']]['type'] == 1){//Label
                    echo '<fieldset class="custom_section_break">
                                    <legend>'.__($arrVals[$arrMap['CustomField']]['name']).'</legend>
                            </fieldset>';
                }elseif($arrVals[$arrMap['CustomField']]['type'] == 2) {//Text
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals[$arrMap['CustomField']]['name'].'</div>
                                    <div class="field_value">';
                                    if(isset($dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'])){
                                        echo $dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'];
                                    }
                              echo '</div>
                            </div>';
                }elseif($arrVals[$arrMap['CustomField']]['type'] == 3) {//DropDown
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals[$arrMap['CustomField']]['name'].'</div>
                                    <div class="field_value">';
                                        if(count($arrVals[$arrMap['CustomFieldOption']])> 0){
                                            $defaults = '';
                                            foreach($arrVals[$arrMap['CustomFieldOption']] as $arrDropDownVal){
                                                //pr($dataValues);
                                                //die;
                                                if(isset($dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'])){
                                                    $defaults =  $dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'];
                                                }
                                                echo ($defaults == $arrDropDownVal['id']?$arrDropDownVal['value']:"");
                                            }
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
                                                echo '<input type="checkbox" disabled '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").'> <label>'.$arrDropDownVal['value'].'</label> ';
                                                
                                            }
                                            
                                        }
                                         
                              echo '</div>
                            </div>';
                }elseif($arrVals[$arrMap['CustomField']]['type'] == 5) {
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals[$arrMap['CustomField']]['name'].'</div>
                                    <div class="field_value">';
                                    if(isset($dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'])){
                                        echo $dataValues[$arrVals[$arrMap['CustomField']]['id']][0]['value'];
                                    }
                              echo '</div>
                            </div>';
                }
                
            }
        
		} ?>
	
</div>