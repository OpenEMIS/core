<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Other Forms'));

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'otherforms', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

	echo $this->Form->create('CensusGridValue', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),	
		'url' => array('controller' => 'Census', 'action' => 'otherformsEdit')
	));
	
echo $this->element('census/year_options');
?>

<div id="infrastructure" class="">
	
	<!-- Custom Grid -->
	<?php $index = 1; foreach($data as $arrval) { ?>
	<div class="custom_grid">
		<fieldset class="section_break">
			<legend><?php echo $arrval['CensusGrid']['name']; ?></legend>
		</fieldset>
		
		<div class="desc"><?php echo $arrval['CensusGrid']['description']; ?></div>
		<div class="x_title"><?php echo $arrval['CensusGrid']['x_title']; ?></div>
		
		<div class="table_wrapper">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
					<th class="table_cell y_col">&nbsp;</th>
					<?php foreach($arrval['CensusGridXCategory'] as $statVal) { ?>
					<th class="table_cell"><?php echo $statVal['name'] ?></th>
					<?php } ?>
					</tr>
				</thead>
				
				<tbody class="table_body" id="<?php echo $statVal['name']; ?>_section">
				<?php
				
				foreach($arrval['CensusGridYCategory']  as $yCatId => $yCatName) {
				?>
					<tr class="table_row">
						<td class="table_cell"><?php echo $yCatName['name']; ?></td>
						<?php foreach($arrval['CensusGridXCategory'] as $xCatId => $xCatName) { ?>
						<td class="table_cell">
							<?php
							echo $this->Form->input($index . '.census_grid_x_category_id', array('type' => 'hidden', 'value' => $xCatName['id']));
							echo $this->Form->input($index . '.census_grid_y_category_id', array('type' => 'hidden', 'value' => $yCatName['id']));
							echo $this->Form->input($index . '.census_grid_id', array('type' => 'hidden', 'value' => $arrval['CensusGrid']['id']));
							
							$gridId = isset($arrval['answer'][$xCatName['id']][$yCatName['id']]['id'])
									? $arrval['answer'][$xCatName['id']][$yCatName['id']]['id']
									: '';
							
							echo $this->Form->input($index . '.id', array('type' => 'hidden', 'value' => $gridId));
							?>
							
							<?php
							$gridVal = isset($arrval['answer'][$xCatName['id']][$yCatName['id']]['value'])
									 ? $arrval['answer'][$xCatName['id']][$yCatName['id']]['value']
									 : '';
								 
							echo $this->Form->input($index++ . '.value', array(
									'type' => 'text',
									'before' => '<div class="input_wrapper">',
									'after' => '</div>',
									'value' => $gridVal
								)
							); 
							?>
						</td>
						<?php } ?>
					</tr>
				<?php } ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php }	?>	
	<!-- End Custom Grid -->
	
	<?php 
	/*
            //pr($data);
            $ctrModel = 1;
            foreach($data as $arrval){
                    
                    echo $arrval['CensusGrid']['name'].'<br><br>';
                    echo '<div align="center" style="font-size:11px;color:#666;margin-bottom:10px">'.$years[$selectedYear].' '.$arrval['CensusGrid']['description'].'</div>';
                    echo '<div class="table" style="white-space:nowrap;overflow-x:scroll;">
                            
                            <div class="head" style="overflow:visible;border-style:none">
                                    <div class="col_age" style="float:none;display:inline-block;"></div>';
                                    foreach($arrval['CensusGridXCategory'] as $statVal){
                                        echo '<div class="col_total" style="vertical-align:middle;height:27px;float:none;display:inline-block;text-align:center;white-space:normal;width:80px;border:1px solid #CCCCCC;background-color:#EFEFEF">'.$statVal['name'].'</div>';

                                    }
                               echo'
                            </div>

                            <div class="records" id="'.$statVal['name'].'_section">';
                               $ctr = 1;
                               
                               foreach($arrval['CensusGridYCategory']  as $yCatId => $yCatName){
                                    echo '<div class="table_row '.($ctr%2==0?'even':'').'" style="overflow:visible;border:0px">
                                            <div class="col_age" style="white-space:normal;display:inline-block;float:s">'.$yCatName['name'].'</div>';
                                    foreach($arrval['CensusGridXCategory'] as $xCatId => $xCatName){

                                        echo '<div class="col_total" style="vertical-align:middle;height:27px;float:none;display:inline-block;text-align:center;white-space:normal;width:80px;border:1px solid #CCCCCC'.($ctr%2==0?';background-color:#F7F7F7':'').';">';
                                                    echo '<input type="hidden" name="data[CensusGridValue]['.$ctrModel.'][census_grid_x_category_id]" value="'.$xCatName['id'].'">';
                                                    echo '<input type="hidden" name="data[CensusGridValue]['.$ctrModel.'][census_grid_y_category_id]" value="'.$yCatName['id'].'">';
                                                    
                                                    echo '<input type="hidden" name="data[CensusGridValue]['.$ctrModel.'][census_grid_id]" value="'.$arrval['CensusGrid']['id'].'">';
                                                    
                                                    echo '<input type="text" style="width:60px;" name="data[CensusGridValue]['.$ctrModel.'][value]" value="'.(isset($arrval['answer'][$xCatName['id']][$yCatName['id']]['value'])?$arrval['answer'][$xCatName['id']][$yCatName['id']]['value']:'').'" >';
                                                    echo '<input type="hidden" name="data[CensusGridValue]['.$ctrModel.'][id]" value="'.(isset($arrval['answer'][$xCatName['id']][$yCatName['id']]['id'])?$arrval['answer'][$xCatName['id']][$yCatName['id']]['id']:'').'">';
                                        
                                        echo '</div>';
                                        //echo '<div class="col_total">'. $statids.'</div>';
                                        $ctrModel++;
                                    }       
                                    echo '</div>';
                                    $ctr++;
                                }

                              echo '
                            </div>
                          </div>';
            echo '<br><br><hr><br>';
            }
            
            */
        //pr($datafields); 
        //pr($datavalues);
            $ctr = 1;
            foreach($datafields as $arrVals){
                if($arrVals['CensusCustomField']['type'] == 1){//Label
                    echo '<fieldset class="section_break">
                                    <legend>'.$arrVals['CensusCustomField']['name'].'</legend>
                            </fieldset>';
                }elseif($arrVals['CensusCustomField']['type'] == 2) {//Text
                    
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
                                    <div class="field_value">'; 
                                    $val = (isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value']))?
                                        $datavalues[$arrVals['CensusCustomField']['id']][0]['value']:"";
                                    
                                    echo '<input type="text" name="data[CensusCustomValue][textbox]['.$arrVals["CensusCustomField"]["id"].'][value]" value="'.$val.'" >';
                              echo '</div>
                            </div>';
                }elseif($arrVals['CensusCustomField']['type'] == 3) {//DropDown
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
                                    <div class="field_value">';
                                       
                                        if(count($arrVals['CensusCustomFieldOption'])> 0){
                                            echo '<select name="data[CensusCustomValue][dropdown]['.$arrVals["CensusCustomField"]["id"].'][value]">';
                                            foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
                                                if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
                                                    $defaults =  $datavalues[$arrVals['CensusCustomField']['id']][0]['value'];
                                                }
                                                echo '<option value="'.$arrDropDownVal['id'].'" '.($defaults == $arrDropDownVal['id']?'selected="selected"':"").'>'.$arrDropDownVal['value'].'</option>';
                                            }
                                            echo '</select>';
                                        }
                              echo '</div>
                            </div>';
                    
                    
                    
                }elseif($arrVals['CensusCustomField']['type'] == 4) {
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
                                    <div class="field_value">';
                                        $defaults = array();
                                         if(count($arrVals['CensusCustomFieldOption'])> 0){
                                            
                                            foreach($arrVals['CensusCustomFieldOption'] as $arrDropDownVal){
                                                
                                                if(isset($datavalues[$arrVals['CensusCustomField']['id']])){
                                                    if(count($datavalues[$arrVals['CensusCustomField']['id']] > 0)){
                                                        foreach($datavalues[$arrVals['CensusCustomField']['id']] as $arrCheckboxVal){
                                                            $defaults[] = $arrCheckboxVal['value'];
                                                        }
                                                    }
                                                    
                                                }
                                                echo '<input name="data[CensusCustomValue][checkbox]['.$arrVals["CensusCustomField"]["id"].'][value][]" type="checkbox" '.(in_array($arrDropDownVal['id'], $defaults) ?'checked':"").' value="'.$arrDropDownVal['id'].'"> <label>'.$arrDropDownVal['value'].'</label> ';
                                                
                                            }
                                            
                                        }
                                         
                              echo '</div>
                            </div>';
                }elseif($arrVals['CensusCustomField']['type'] == 5) {
                    echo '<div class="custom_field">
                                    <div class="field_label">'.$arrVals['CensusCustomField']['name'].'</div>
                                    <div class="field_value">';
                                    if(isset($datavalues[$arrVals['CensusCustomField']['id']][0]['value'])){
                                        $val = ($datavalues[$arrVals['CensusCustomField']['id']][0]['value']?$datavalues[$arrVals['CensusCustomField']['id']][0]['value']:""); 
                                    }
                                    echo '<textarea name="data[CensusCustomValue][textarea]['.$arrVals["CensusCustomField"]["id"].'][value]">'.$val.'</textarea>';
                              echo '</div>
                            </div>';
                }
                
            }
        
        
            
            
            
        ?>
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'otherforms', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	<?php echo $this->Form->end(); ?>
	
</div>
<?php $this->end(); ?>