<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_finance', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'finances', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

echo $this->Form->create('CensusFinance', array(
                    'id' => 'submitForm',
                    'inputDefaults' => array('label' => false, 'div' => false),
                    'url' => array(
                            'controller' => 'Census', 
                            'action' => 'financesEdit'
                    )
            )
    );
echo $this->element('census/year_options');
?>
<div id="finances" class="content_wrapper edit">
	<?php
        //pr($data);
		$ctr = 0;
        if(count($data) > 0){
        foreach($data as $nature => $arrFinanceType){
            echo '<fieldset class="section_group">
                        <legend>'.$nature.'</legend>';
                 foreach($arrFinanceType as $finance => $arrCategories){
                    echo '<fieldset class="section_break">
                                <legend>'.$finance.'</legend>
                                <table class="table table-striped table-hover table-bordered">
									<thead>
                                                                        <tr>
										<th class="table_cell">'.__('Source').'</th>
										<th class="table_cell">'.__('Category').'</th>
										<th class="table_cell">'.__('Description').'</th>
										<th class="table_cell">'.__('Amount').'</th>
										<th class="table_cell cell_delete">&nbsp;</th>
                                                                        </tr>
									</thead>
									<tbody>';
                                    foreach($arrCategories as $arrValues){
                                        //pr($arrCategories);die;
										$record_tag="";
										switch ($arrValues['CensusFinance']['source']) {
											case 1:
												$record_tag.="row_external";break;
											case 2:
												$record_tag.="row_estimate";break;
										}
										
                                        echo '<tr id="bankaccount_row_'.$arrValues['CensusFinance']['id'].'" >
													<input type="hidden" name="data[CensusFinance]['.$ctr.'][id]" value="'.$arrValues['CensusFinance']['id'].'">
                                                    <td class="table_cell">
                                                        <select name="data[CensusFinance]['.$ctr.'][finance_source_id]" class="full_width form-control '. $record_tag .'" >';
                                                        
                                                        
                                                        foreach($sources as $i => $v){
                                                            echo '<option value="'.$i.'" '.($i == $arrValues['CensusFinance']['finance_source_id']?'selected="selected"':"").'>'.$v.'</option>';
                                                        }
                                                        echo '<select>
                                                    </td>
                                                    <td class="table_cell">
                                                        <select name="data[CensusFinance]['.$ctr.'][finance_category_id]" class="full_width form-control '. $record_tag .'">';
                                                        foreach($arrValues['CategoryTypes'] as $i=>$v){
                                                            echo '<option value="'.$i.'" '.($i == $arrValues['CensusFinance']['finance_category_id']?'selected="selected"':"").'>'.$v.'</option>';
                                                        }
                                                   echo '<select>
                                                    </td>
                                                    <td class="table_cell">
														<div class="input_wrapper">
															<input class="'. $record_tag .'" type="text" name="data[CensusFinance]['.$ctr.'][description]" value="'.$arrValues['CensusFinance']['description'].'"></div></td>
													<td class="table_cell">
														<div class="input_wrapper">
															<input class="'. $record_tag .'" type="text" name="data[CensusFinance]['.$ctr.'][amount]" value="'.$arrValues['CensusFinance']['amount'].'"></div></td>
                                                    <td class="table_cell"><span class="icon_delete" title="'.__('Delete').'"" onClick="CensusFinance.confirmDeletedlg('.$arrValues['CensusFinance']['id'].')"></span></td>
                                              </tr>';
												   $ctr++;
                                    }     
                                  echo '</tbody>
                                </table>
                          </fieldset>';
					 
                 }
            echo    '</fieldset>';
        }
        echo '<div class="controls">
            <input type="submit" value="'.__('Save').'" class="btn_save btn_right" onClick="return CensusFinance.validateEdit();" />
            <input type="button" value="'.__('Cancel').'" class="btn_cancel btn_left" onClick="CensusFinance.BacktoList(\'CensusFinanceAdd\')" />
        </div>';
        }else{
			echo '<div class="controls" style="border:0">'.__('No Available Finance Records').'</div>';
			
		}
        ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>