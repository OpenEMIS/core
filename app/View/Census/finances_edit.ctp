<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_finance', false);
?>

<?php echo $this->element('breadcrumb'); ?>
<?php
    echo $this->Form->create('CensusFinance', array(
                    'id' => 'submitForm',
                    'inputDefaults' => array('label' => false, 'div' => false),
                    'url' => array(
                            'controller' => 'Census', 
                            'action' => 'financesEdit'
                    )
            )
    );
?>
<div id="finances" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Finances'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'finances'), array('id' => 'edit-link', 'class' => 'divider')); ?>
		
	</h1>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<select id="SchoolYearId" >
				<?php 
					foreach($years as $id => $year){
						echo '<option value="'.$id.'" '.($selectedYear == $id?'selected="selected"':'').'>'.$year.'</option>';
					}
				?>
			</select>
		</div>
		<div style="float:right;">
		<ul class="legend">
			<li><span class="dataentry"></span><?php echo __('Data Entry'); ?></li>
			<li><span class="external"></span><?php echo __('External'); ?></li>
			<li><span class="estimate"></span><?php echo __('Estimate'); ?></li>
		</ul>
		</div>
	</div>
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
                                <div class="table">
									<div class="table_head">
										<div class="table_cell">'.__('Source').'</div>
										<div class="table_cell">'.__('Category').'</div>
										<div class="table_cell">'.__('Description').'</div>
										<div class="table_cell">'.__('Amount').'</div>
										<div class="table_cell cell_delete">&nbsp;</div>
									</div>
									<div class="table_body">';
                                    foreach($arrCategories as $arrValues){
                                        //pr($arrCategories);die;
										$record_tag="";
										switch ($arrValues['CensusFinance']['source']) {
											case 1:
												$record_tag.="row_external";break;
											case 2:
												$record_tag.="row_estimate";break;
										}
										
                                        echo '<div class="table_row" id="bankaccount_row_'.$arrValues['CensusFinance']['id'].'" >
													<input type="hidden" name="data[CensusFinance]['.$ctr.'][id]" value="'.$arrValues['CensusFinance']['id'].'">
                                                    <div class="table_cell">
                                                        <select name="data[CensusFinance]['.$ctr.'][finance_source_id]" class="full_width '. $record_tag .'" >';
                                                        
                                                        
                                                        foreach($sources as $i => $v){
                                                            echo '<option value="'.$i.'" '.($i == $arrValues['CensusFinance']['finance_source_id']?'selected="selected"':"").'>'.$v.'</option>';
                                                        }
                                                        echo '<select>
                                                    </div>
                                                    <div class="table_cell">
                                                        <select name="data[CensusFinance]['.$ctr.'][finance_category_id]" class="full_width '. $record_tag .'">';
                                                        foreach($arrValues['CategoryTypes'] as $i=>$v){
                                                            echo '<option value="'.$i.'" '.($i == $arrValues['CensusFinance']['finance_category_id']?'selected="selected"':"").'>'.$v.'</option>';
                                                        }
                                                   echo '<select>
                                                    </div>
                                                    <div class="table_cell">
														<div class="input_wrapper">
															<input class="'. $record_tag .'" type="text" name="data[CensusFinance]['.$ctr.'][description]" value="'.$arrValues['CensusFinance']['description'].'"></div></div>
													<div class="table_cell">
														<div class="input_wrapper">
															<input class="'. $record_tag .'" type="text" name="data[CensusFinance]['.$ctr.'][amount]" value="'.$arrValues['CensusFinance']['amount'].'"></div></div>
                                                    <div class="table_cell"><span class="icon_delete" title="'.__('Delete').'"" onClick="CensusFinance.confirmDeletedlg('.$arrValues['CensusFinance']['id'].')"></span></div>
                                              </div>';
												   $ctr++;
                                    }     
                                  echo '</div>
                                </div>
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