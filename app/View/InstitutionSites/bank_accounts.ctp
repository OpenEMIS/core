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
			echo $this->Html->link(__('Add'), array('action' => 'bankAccountsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
	 <?php echo $this->element('alert'); ?>
		
	<fieldset class="section_group">
		<legend><?php echo __('Bank Details'); ?></legend>
		
		<div class="table allow_hover full_width" action="InstitutionSites/bankAccountsView/">
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
					   echo '<div class="table_row" row-id="' . $arrVal['InstitutionSiteBankAccount']['id'] . '">
								<div class="table_cell cell_active">'.($arrVal['InstitutionSiteBankAccount']['active'] == 1?"✔":"").'</div>
								<div class="table_cell">'.$arrVal['InstitutionSiteBankAccount']['account_name'].'</div>
								<div class="table_cell">'.$arrVal['InstitutionSiteBankAccount']['account_number'].'</div>
								<div class="table_cell">'.(!isset($banklist[$arrVal['BankBranch']['bank_id']])?"":$banklist[$arrVal['BankBranch']['bank_id']]).'</div>
								<div class="table_cell">'.(!isset($arrVal['BankBranch']['name'])?"":$arrVal['BankBranch']['name']).'</div>
						</div>';
					}
				}
				?>
				<!--div class="table_row">
					<div class="col_acctname">BULU ELEMENTARY</div>
					<div class="col_acctno">1001087792</div>
					<div class="col_branchcode">ABC</div>
					<div class="col_branch">Branch A</div>
				</div>
				<div class="table_row even">
					<div class="col_acctname">BULU SECONDARY</div>
					<div class="col_acctno">1001087792</div>
					<div class="col_branchcode">DEF</div>
					<div class="col_branch">Branch B</div>
				</div-->
			</div>
		</div>
	</fieldset>

</div>