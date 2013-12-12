<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
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
	
	<?php 
	echo $this->Form->create('InstitutionSiteBankAccount', array(
			'id' => 'InstitutionSiteBankAccount',
			'url' => array('controller' => 'InstitutionSites', 'action' => 'bankAccountsEdit', $id, $selectedBank),
			'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
		));
	?>
	<?php echo $this->Form->input('InstitutionSiteBankAccount.id');?>
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
        	<?php echo $this->Form->input('active', array('label'=>false, 'options'=>array('1'=>'Yes', '0'=>'No'))); ?>
        </div>
    </div>

	<div class="row edit">
        <div class="label"><?php echo __('Bank'); ?></div>
        <div class="value">
        	<?php
                echo $this->Form->input('bank_id', array(
                    'options' => $bank,
                    'default' => $selectedBank,
                    'label' => false,
                    'empty' => __('--Select--'),
                    'url' => sprintf('%s/%s/%s', $this->params['controller'], $this->params['action'], $id),
                    'onchange' => 'jsForm.change(this)'
                ));
            ?>
        </div>
    </div>
	<div class="row edit">
		<div class="label"><?php echo __('Branch'); ?></div>
		<div class="value"><?php echo $this->Form->input('bank_branch_id', array($this->request->data['InstitutionSiteBankAccount']['bank_branch_id'], 'label'=>false, 'options'=>$bankBranch, 'empty' => __('--Select--'))); ?></div>
	</div>

	<div class="row edit">
		<div class="label"><?php echo __('Remarks'); ?></div>
		<div class="value"><?php echo $this->Form->input('remarks', array('type'=>'textarea', 'class' => 'default', 'label'=>false)); ?></div>
	</div>
	
	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onclick="return BankAccounts.validateAdd();" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'BankAccounts'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
