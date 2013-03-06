<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('bankaccounts', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bank_accounts" class="content_wrapper edit">
	<h1>
		<span><?php echo __('Bank Accounts'); ?></span>
		<?php echo $this->Html->link(__('View'), array('action' => 'bankAccounts'), array('class' => 'divider')); ?>
	</h1>
	<fieldset class="section_group">
		<legend><?php echo __('Bank Details'); ?></legend>
		<?php
		echo $this->Form->create('InstitutionSiteBankAccount', array(
			'url' => array('controller' => 'InstitutionSites', 'action' => 'bankAccountsEdit')
		));
		?>
		<div class="table">
			<div class="table_head">
                <div class="table_cell cell_radio">&nbsp;</div>
                <div class="table_cell"><?php echo __('Account Name'); ?></div>
				<div class="table_cell"><?php echo __('Account Number'); ?></div>
				<div class="table_cell"><?php echo __('Bank'); ?></div>
				<div class="table_cell"><?php echo __('Branch'); ?></div>
				<div class="table_cell cell_delete">&nbsp;</div>
			</div>
			
			<div class="table_body">
			<?php
			if(count($data) > 0){
				$ctr = 1;
				//pr($bank);
				//die;
				foreach($data as $arrVal){
				   $bnkbrnchdata = array();
				   echo '<div class="table_row" id="bankaccount_row_'.$arrVal['InstitutionSiteBankAccount']['id'].'">
							<div class="table_cell">
								<input type="hidden" name="data[InstitutionSiteBankAccount]['.$ctr.'][id]" value="'.$arrVal['InstitutionSiteBankAccount']['id'].'" />
								<input type="hidden" name="data[InstitutionSiteBankAccount]['.$ctr.'][active]" value="0" />
								<input name="data[InstitutionSiteBankAccount][active]" value="'.$arrVal['InstitutionSiteBankAccount']['id'].'" type="radio" '.($arrVal['InstitutionSiteBankAccount']['active'] == 1?'checked':'').'/>
							</div>
							<div class="table_cell">
								<div class="input_wrapper">
									<input type="text" name="data[InstitutionSiteBankAccount]['.$ctr.'][account_name]" value="'.$arrVal['InstitutionSiteBankAccount']['account_name'].'" />
								</div>
							</div>
							<div class="table_cell">
								<div class="input_wrapper">
									<input type="text" name="data[InstitutionSiteBankAccount]['.$ctr.'][account_number]" value="'.$arrVal['InstitutionSiteBankAccount']['account_number'].'"/>
								</div>
							</div>
							<div class="table_cell">
								<select onchange="BankAccounts.changeBranch(this)" name="data[Bank][bank_id]" class="full_width">
									<option value="0">' . __('--Select--') . '</option>
							';
							
							foreach ($bank as $arrBankandBranches) {
								$selectdbank = 0;
								foreach($arrBankandBranches['BankBranch'] as $arrIn){
									if($arrIn['id'] == $arrVal['InstitutionSiteBankAccount']['bank_branch_id']){
										$bnkbrnchdata = $arrBankandBranches['BankBranch'];
										$selectdbank = 1;
										break;
									}
								}
								
								echo "<option ".($selectdbank == 1?'selected="selected"':'')." value='".$arrBankandBranches['Bank']['id']."'>".$arrBankandBranches['Bank']['name']."</option>";
								
							}

							echo '</select>
							</div>
							<div class="table_cell">
								<select name="data[InstitutionSiteBankAccount]['.$ctr.'][bank_branch_id]">
									<option value="0">' . __('--Select--') . '</option>';
								foreach($bnkbrnchdata as $arrbranches){
									echo '<option value="'.$arrbranches['id'].'" '.($arrbranches['id'] == $arrVal['InstitutionSiteBankAccount']['bank_branch_id']?' selected="selected" ':"").'>'.$arrbranches['name'].'</option>';
								}
							echo '</select>
							</div>
							<div class="table_cell"><span class="icon_delete" title="'.__("Delete").'" onClick="BankAccounts.confirmDeletedlg('.$arrVal['InstitutionSiteBankAccount']['id'].')"></span></div>
					</div>';
				   $ctr++;
				}
			}
			?>
			</div>
		</div>
		
		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return BankAccounts.validateAdd();" />
			<?php echo $this->Html->link(__('Cancel'), array('action' => 'bankAccounts'), array('class' => 'btn_cancel btn_left')); ?>
		</div>
		<?php echo $this->Form->end(); ?>
	</fieldset>
</div>