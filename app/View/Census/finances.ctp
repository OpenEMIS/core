<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('census', 'stylesheet', array('inline' => false));

echo $this->Html->script('census', false);
echo $this->Html->script('census_finance', false);
echo $this->Html->script('jquery.scrollTo', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="finances" class="content_wrapper">
	<?php
	echo $this->Form->create('CensusFinance', array(
		'id' => 'submitForm',
		'inputDefaults' => array('label' => false, 'div' => false),
		'url' => array('controller' => 'Census', 'action' => 'finances')
	));
	?>
	<h1>
		<span><?php echo __('Finances'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array(), array('class' => 'divider void', 'onclick' => "CensusFinance.show('CensusFinanceAdd')"));
		}
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'financesEdit'), array('id' => 'edit-link', 'class' => 'divider'));
		}
		?>
	</h1>
	
	<div class="row year">
		<div class="label"><?php echo __('Year'); ?></div>
		<div class="value">
			<?php
			echo $this->Form->input('school_year_id', array(
				'id' => 'SchoolYearId',
				'options' => $years,
				'default' => $selectedYear
			));
			?>
		</div>
		<div class="row_item_legend">
		<ul class="legend">
			<li><span class="dataentry"></span><?php echo __('Data Entry'); ?></li>
			<li><span class="external"></span><?php echo __('External'); ?></li>
			<li><span class="estimate"></span><?php echo __('Estimate'); ?></li>
		</ul>
		</div>
	</div>
	<?php
        //pr($data);
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
										<div class="table_cell">'.__('Amount').' ('.$this->Session->read('configItem.currency').')</div>
									</div>
									<div class="table_body">';
                                    foreach($arrCategories as $arrValues){
                                        //pr($arrCategories);
                                        //echo "d2";
										$record_tag="";
										switch ($arrValues['CensusFinance']['source']) {
											case 1:
												$record_tag.="row_external";break;
											case 2:
												$record_tag.="row_estimate";break;
										}
                                        echo '<div class="table_row">
												<div class="table_cell '. $record_tag .'">'.$arrValues['FinanceSource']['name'].'</div>
												<div class="table_cell '. $record_tag .'">'.$arrValues['FinanceCategory']['name'].'</div>
												<div class="table_cell '. $record_tag .'">'.$arrValues['CensusFinance']['description'].'</div>
												<div class="table_cell '. $record_tag .'">'.$arrValues['CensusFinance']['amount'].'</div>
                                              </div>';
                                    }     
                                  echo '</div>
                                </div>
                          </fieldset>';
                 }
            echo    '</fieldset>';
        }
	?>
		
	<fieldset id="CensusFinanceAdd" class="section_group" style="<?php ((count($data)>0)?'visibility: hidden':'');?>">
		<legend><?php echo __('Add New'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Nature'); ?></div>
				<div class="table_cell"><?php echo __('Type'); ?></div>
				<div class="table_cell"><?php echo __('Category'); ?></div>
				<div class="table_cell"><?php echo __('Source'); ?></div>
				<div class="table_cell"><?php echo __('Description'); ?></div>
				<div class="table_cell"><?php echo __('Amount'); ?></div>
			</div>
			<div class="table_body">
				<div class="table_row">
					<div class="table_cell">
						<select id="FinanceNature" onChange="CensusFinance.changeType(this)" name="data[FinanceNature][id]" class="full_width">
							<option value="0"><?php echo __('--Select--'); ?></option>
							<?php 
							foreach($natures as $id => $name){
								echo '<option value="'.$id.'">'.$name.'</option>';
							}
							?>
						</select>
					</div>
					<div class="table_cell">
						<select id="FinanceType" onChange="CensusFinance.changeCategory(this)" name="data[FinanceType][id]" class="full_width">
							<option value="0"><?php echo __('--Select--'); ?></option>
						</select>
					</div>
					<div class="table_cell">
						<select id="FinanceCategory" name="data[CensusFinance][finance_category_id]" class="full_width">
							<option value="0"><?php echo __('--Select--'); ?></option>
						</select>
					</div>
					<div class="table_cell">
						<select id="FinanceSource" name="data[CensusFinance][finance_source_id]" class="full_width" class="full_width">
							<option value="0"><?php echo __('--Select--'); ?></option>
							<?php 
							foreach($sources as $id => $name){
								echo '<option value="'.$id.'">'.$name.'</option>';
							}
							?>
						</select>
					</div>
					<div class="table_cell"><div class="input_wrapper"><input type="text" name="data[CensusFinance][description]"></div></div>
					<div class="table_cell"><div class="input_wrapper"><input type="text" name="data[CensusFinance][amount]"></div></div>
				</div>
			</div>			
			<input type="hidden" name="data[CensusFinance][school_year_id]" value="<?php echo $selectedYear;?>">
		</div>
		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return CensusFinance.validateAdd();" />
			<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" onClick="CensusFinance.hide('CensusFinanceAdd')" />
		</div>
	</fieldset>	
</div>

<?php echo $this->Form->end(); ?>