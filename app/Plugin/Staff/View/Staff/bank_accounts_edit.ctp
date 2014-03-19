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
        if ($_edit && !$WizardMode) {
            echo $this->Html->link(__('Back'), array('action' => 'bankAccountsView', $id), array('class' => 'divider'));
        }
        ?>
    </h1>
    <?php echo $this->element('alert'); ?>
	<div class="bankAccountForm">
	<?php 
	echo $this->Form->create('StaffBankAccount', array(
			'id' => 'StaffBankAccount',
			'url' => array('controller' => 'Staff', 'action' => 'bankAccountsEdit', $id, $selectedBank),
			'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
		));
	?>
	<?php echo $this->Form->input('StaffBankAccount.id');?>
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
        	<?php echo $this->Form->input('active', array('options'=>array('1'=>'Yes', '0'=>'No'))); ?>
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
                    'onchange' => 'BankAccounts.getBranchesAfterChangeBank(this)'
                ));
            ?>
        </div>
    </div>
	<div class="row edit branch">
		<div class="label"><?php echo __('Branch'); ?></div>
		<div class="value"><?php echo $this->Form->input('bank_branch_id', array($this->request->data['StaffBankAccount']['bank_branch_id'], 'label'=>false, 'options'=>$bankBranch, 'empty' => __('--Select--'))); ?></div>
	</div>

	<div class="row edit">
		<div class="label"><?php echo __('Remarks'); ?></div>
		<div class="value"><?php echo $this->Form->input('remarks', array('type'=>'textarea', 'class' => 'default', 'label'=>false)); ?></div>
	</div>
	
	<div class="controls">
		<?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <?php echo $this->Html->link(__('Cancel'), array('action' => 'bankAccountsView',$id), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_cancel_button btn_right"));
                
                if(!$wizardEnd){
                    echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }else{
                    echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
                }
                if($mandatory!='1' && !$wizardEnd){
                    echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_left"));
                } 
      } ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
	</div>
</div>
