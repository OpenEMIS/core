<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
echo $this->Html->script('bankaccounts', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bank_accounts" class="content_wrapper">
	<h1>
		<span><?php echo __('Bank Accounts'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array(), array('class' => 'divider void', 'onclick' => "BankAccounts.show('BankAccountsAdd')"));
		}
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'bankAccountsEdit'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<fieldset class="section_group">
		<legend><?php echo __('Bank Details'); ?></legend>
		
		<div class="table">
			<div class="table_head">
				<div class="table_cell cell_active"><?php echo __('Active'); ?></div>
				<div class="table_cell"><?php echo __('Account Name'); ?></div>
				<div class="table_cell"><?php echo __('Account Number'); ?></div>
				<div class="table_cell"><?php echo __('Bank'); ?></div>
				<div class="table_cell"><?php echo __('Branch'); ?></div>
			</div>
			
			<div class="table_body">
				<?php
				if(count($data) > 0){
					foreach($data as $arrVal){
					   echo '<div class="table_row">
								<div class="table_cell cell_active">'.($arrVal['StudentBankAccount']['active'] == 1?"✔":"").'</div>
								<div class="table_cell">'.$arrVal['StudentBankAccount']['account_name'].'</div>
								<div class="table_cell">'.$arrVal['StudentBankAccount']['account_number'].'</div>
								<div class="table_cell">'.(!isset($banklist[$arrVal['BankBranch']['bank_id']])?"":$banklist[$arrVal['BankBranch']['bank_id']]).'</div>
								<div class="table_cell">'.(!isset($arrVal['BankBranch']['name'])?"":$arrVal['BankBranch']['name']).'</div>
						</div>';
					}
				}
				?>
			</div>
		</div>
	</fieldset>
	<?php
	if($_add) {
		echo $this->Form->create('StudentBankAccount', array(
			'id' => 'StudentBankAccount',
			'url' => array('controller' => 'Students', 'action' => 'bankAccounts')
		));
	?>
	<fieldset id="BankAccountsAdd" class="section_group" <?php echo (count($data) > 0?'style="visibility:hidden"':""); ?>>
		<legend><?php echo __('Add New'); ?></legend>
	
		<div class="table">
			<div class="table_head">
				<div class="table_cell"><?php echo __('Account Name'); ?></div>
				<div class="table_cell"><?php echo __('Account Number'); ?></div>
				<div class="table_cell"><?php echo __('Bank'); ?></div>
				<div class="table_cell"><?php echo __('Branch'); ?></div>
			</div>
		
			<div class="table_body">
			<?php
			$bnkbrnchdata = array();
			echo '<div class="table_row">
					<div class="table_cell">
						<div class="input_wrapper">
							<input type="text" name="data[StudentBankAccount][account_name]" value="" />
						</div>
					</div>
					<div class="table_cell">
						<div class="input_wrapper">
							<input type="text" name="data[StudentBankAccount][account_number]" value=""/>
						</div>
					</div>
					<div class="table_cell">
						<select onchange="BankAccounts.changeBranch(this)" name="data[Bank][bank_id]" class="full_width">
							<option value="0" selected="selected">'. __('--Select--') .'</option>
					';
					foreach ($bank as $arrBankandBranches) {
						
						echo "<option value='".$arrBankandBranches['Bank']['id']."'>".$arrBankandBranches['Bank']['name']."</option>";
					}
					echo '</select>
					</div>
					<div class="table_cell">
						<select name="data[StudentBankAccount][bank_branch_id]" class="full_width">
							<option value="0">'. __('--Select--').'</option>
						</select>
					</div>
					
			</div>';
			?>
			</div>
		</div>
		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="return BankAccounts.validateAdd();" />
			<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" onClick="BankAccounts.hide('BankAccountsAdd')" />
		</div>
	</fieldset>
	<?php
		echo $this->Form->end();
	} //end if $_add
	?>
</div>