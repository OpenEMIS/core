<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('search', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bankAccountView" class="content_wrapper">
    <h1>
        <span><?php echo __('Bank Accounts'); ?></span>
		<?php
		$data = $bankAccountObj[0]['TeacherBankAccount'];
		echo $this->Html->link(__('List'), array('action' => 'bankAccounts'), array('class' => 'divider'));
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'bankAccountsEdit', $data['id']), array('class' => 'divider'));
		}
		if($_delete) {
			echo $this->Html->link(__('Delete'), array('action' => 'bankAccountsDelete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
		}
		?>
    </h1>
    <?php echo $this->element('alert'); ?>
    
    <div class="row">
		<div class="label"><?php echo __('Account Name'); ?></div>
		<div class="value"><?php echo $data['account_name']; ?></div>
	</div>

    <div class="row">
        <div class="label"><?php echo __('Account Number'); ?></div>
        <div class="value"><?php echo $data['account_number']; ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Active'); ?></div>
        <div class="value"><?php echo ($data['active'] == 1?"Yes":"No"); ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Bank'); ?></div>
        <div class="value"><?php echo (!isset($banklist[$bankAccountObj[0]['BankBranch']['bank_id']])?"":$banklist[$bankAccountObj[0]['BankBranch']['bank_id']]) ?></div>
    </div>

    <div class="row">
        <div class="label"><?php echo __('Branch'); ?></div>
        <div class="value"><?php echo (!isset($bankAccountObj[0]['BankBranch']['name'])?"":$bankAccountObj[0]['BankBranch']['name']); ?></div>
    </div>

      <div class="row">
        <div class="label"><?php echo __('Remarks'); ?></div>
        <div class="value"><?php echo $data['remarks']; ?></div>
    </div>

     <div class="row">
        <div class="label"><?php echo __('Modified by'); ?></div>
        <div class="value"><?php echo trim($bankAccountObj[0]['ModifiedUser']['first_name'] . ' ' . $bankAccountObj[0]['ModifiedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Modified on'); ?></div>
        <div class="value"><?php echo $data['modified']; ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created by'); ?></div>
        <div class="value"><?php echo trim($bankAccountObj[0]['CreatedUser']['first_name'] . ' ' . $bankAccountObj[0]['CreatedUser']['last_name']); ?></div>
    </div>
    
    <div class="row">
        <div class="label"><?php echo __('Created on'); ?></div>
        <div class="value"><?php echo $data['created']; ?></div>
    </div>

    
</div>
