<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('bankaccounts', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bankAccountAdd" class="content_wrapper add">
    <h1>
        <span><?php echo __('Add Bank Account'); ?></span>
    </h1>
    <?php echo $this->element('alert'); ?>
	
	<?php 
	echo $this->Form->create('StudentBankAccount', array(
			'id' => 'StudentBankAccount',
			'url' => array('controller' => 'Students', 'action' => 'bankAccountsAdd'),
			'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
		));
	?>
	<div class="bankAccountForm">
	<?php echo $this->Form->input('student_id', array('type'=>'hidden', 'value'=>$student_id)); ?>
	<div class="row edit">
        <div class="label"><?php echo __('Account Name'); ?></div>
        <div class="value"><?php echo $this->Form->input('account_name'); ?></div>
    </div>
    
    <div class="row edit">
		<div class="label"><?php echo __('Account Number'); ?></div>
		<div class="value"><?php echo $this->Form->input('account_number'); ?></div>
	</div>

	<div class="row edit">
        <div class="label"><?php echo __('Active'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('active', array('options'=>array('1'=>__('Yes'), '0'=>__('No')))); ?>
        </div>
    </div>

	<div class="row edit">
        <div class="label"><?php echo __('Bank'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('bank_id', array('options'=>$bank, 'onchange'=>"BankAccounts.changeBranch(this)", 'empty' => __('--Select--'))); ?>
        </div>
    </div>
	<div class="row edit branch">
		<div class="label"><?php echo __('Branch'); ?></div>
		<div class="value"><?php echo $this->Form->input('bank_branch_id', array('options'=>array(), 'empty' => __('--Select--'))); ?></div>
	</div>

	<div class="row edit">
		<div class="label"><?php echo __('Remarks'); ?></div>
		<div class="value"><?php echo $this->Form->input('remarks', array('type'=>'textarea', 'class' => 'default', 'label'=>false)); ?></div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'BankAccounts'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
	</div>
</div>
