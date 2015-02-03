<?php
echo $this->Html->script('custom_field', false);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Academic'));

$this->start('contentActions');
if(isset($myview)){
		        echo $this->Html->link(__('Back'), array('controller' => $this->params['controller'], 'action' => $myview, $id), array('class' => 'divider'));
		    }
			if(@$displayEdit){
				if($_edit) {
					echo $this->Html->link(__('Edit'), array('action' => rtrim($this->action,'View').'Edit',$id),	array('class' => 'divider','onClick'=>'return custom.view.redirect(this)')); 
				}
			}
$this->end();

$this->start('contentBody');
?>
<div class="content_wrapper">
	<div class="row year">
		<div class="col-md-2"><?php echo __('Academic Period'); ?></div>
		<div class="col-md-3">
			<?php
				echo $this->Form->input('academic_period_id', array(
					'label' => false,
					'div' => false,
					'class' => 'form-control',
					'options' => $academic_periods,
					'default' => $selectedAcademicPeriod,
					'onchange' => 'custom.view.changeCategory(this)',
					'url' => $this->params['controller']. '/' . $this->action .'/'.$id 
				));
			?>
		</div>
	</div>
	<?php if(isset($institution_sites)) { ?>
	<div class="row" >
		<div class="col-md-2"><?php echo __('Institution Sites'); ?></div>
		<div class="col-md-4" style="margin-bottom: 10px;">
			<?php
				echo $this->Form->input('institution_site_id', array(
					'label' => false,
					'div' => false,
					'class' => 'form-control',
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
                    echo '<fieldset class="section_break">
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
        
		
		
	}
	?>
	</div>
<?php 	$this->end();?>