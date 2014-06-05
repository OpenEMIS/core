<?php /*

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
		
	<div class="table allow_hover full_width" action="Staff/bankAccountsView/">
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
				   echo '<div class="table_row" row-id="' . $arrVal['StaffBankAccount']['id'] . '">
							<div class="table_cell cell_active">'.($arrVal['StaffBankAccount']['active'] == 1?"✔":"").'</div>
							<div class="table_cell">'.$arrVal['StaffBankAccount']['account_name'].'</div>
							<div class="table_cell">'.$arrVal['StaffBankAccount']['account_number'].'</div>
							<div class="table_cell">'.(!isset($banklist[$arrVal['BankBranch']['bank_id']])?"":$banklist[$arrVal['BankBranch']['bank_id']]).'</div>
							<div class="table_cell">'.(!isset($arrVal['BankBranch']['name'])?"":$arrVal['BankBranch']['name']).'</div>
					</div>';
				}
			}
			?>
		</div>
	</div>
</div>
 * 
 */?>

<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'bankAccountsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Active'), __('Account Name'), __('Account Number'), __('Bank'), __('Branch'));
$tableData = array();


foreach ($data as $obj) {
    $symbol = $this->Utility->checkOrCrossMarker($obj[$model]['active'] == 1);
    $row = array();
    $row[] = array($symbol, array('class' => 'center'));
    $row[] = $this->Html->link($obj[$model]['account_name'], array('action' => 'bankAccountsView', $obj[$model]['id']), array('escape' => false));
    $row[] = $obj[$model]['account_number'];
    $row[] = $obj['BankBranch']['Bank']['name'];
    $row[] = $obj['BankBranch']['name'];
    $tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

$this->end();
?>
