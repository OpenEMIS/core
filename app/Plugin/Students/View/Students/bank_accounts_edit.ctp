<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('bankaccounts', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bankAccountEdit" class="content_wrapper add">
   <h1>
        <span><?php echo __('Bank Accounts'); ?></span>
        <?php 
        if ($_edit) {
            echo $this->Html->link(__('Back'), array('action' => 'bankAccountsView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
	<div class="bankAccountForm">
	<?php 
	echo $this->Form->create('StudentBankAccount', array(
			'id' => 'StudentBankAccount',
			'url' => array('controller' => 'Students', 'action' => 'bankAccountsEdit'),
			'inputDefaults' => array('error' => array(
                'attributes' => array(
                     'wrap' => 'label', 'class' => 'error-message', 'style' => 'margin:0px;width:225px;max-width:225px;'
                 )
           ))
		));
	?>
	<?php echo $this->Form->input('StudentBankAccount.id');?>
	<div class="row edit">
        <div class="label"><?php echo __('Account Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('account_name', array('class' => 'default', 'label'=>false)); ?></div>
    </div>
    
    <div class="row edit">
		<div class="label"><?php echo __('Account Number'); ?></div>
		<div class="value"><?php echo $this->Form->input('account_number', array('class' => 'default', 'label'=>false)); ?></div>
	</div>

	<div class="row edit">
        <div class="label"><?php echo __('Active'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('active', array('class' => 'full_width', 'label'=>false, 'options'=>array('1'=>'Yes', '0'=>'No'))); ?>
        </div>
    </div>

	<div class="row edit">
        <div class="label"><?php echo __('Bank'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('BankBranch.bank_id', array('class' => 'full_width', 'label'=>false, 'options'=>$bank, 'default'=>$this->request->data['BankBranch']['bank_id'], 'onchange'=>"BankAccounts.changeBranch(this)", 'empty' => __('--Select--'))); ?>
        </div>
    </div>
	<div class="row edit">
		<div class="label"><?php echo __('Branch'); ?></div>
		<div class="value"><?php echo $this->Form->input('bank_branch_id', array('class' => 'full_width', $this->request->data['StudentBankAccount']['bank_branch_id'], 'label'=>false, 'options'=>$bankBranch, 'empty' => __('--Select--'))); ?></div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="return BankAccounts.validateAdd();" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'BankAccounts'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
	</div>
</div>
