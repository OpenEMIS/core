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
			'url' => array('controller' => 'Students', 'action' => 'bankAccountsAdd', $selectedBank),
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
        	<?php
                echo $this->Form->input('bank_id', array(
                    'options' => $bank,
                    'default' => $selectedBank,
                    'label' => false,
                    'empty' => __('--Select--'),
                    'url' => sprintf('%s/%s', $this->params['controller'], $this->params['action']),
                    'onchange' => 'BankAccounts.getBranchesAfterChangeBank(this)'
                ));
            ?>
        </div>
    </div>
	<div class="row edit branch">
		<div class="label"><?php echo __('Branch'); ?></div>
		<div class="value"><?php echo $this->Form->input('bank_branch_id', array('options'=>$bankBranches, 'empty' => __('--Select--'))); ?></div>
	</div>

	<div class="row edit">
		<div class="label"><?php echo __('Remarks'); ?></div>
		<div class="value"><?php echo $this->Form->input('remarks', array('type'=>'textarea', 'class' => 'default', 'label'=>false)); ?></div>
	</div>
	
	<div class="controls">
		 <?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
        <input type="button" value="<?php echo __("Cancel"); ?>" class="btn_cancel btn_left" url="Students/bankAccounts" onclick="jsForm.goto(this)"/>
        <?php }else{?>
            <?php 
                if(!$mandatory){
                echo $this->Form->hidden('nextLink', array('value'=>$nextLink)); 
                echo $this->Form->submit('Skip', array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_right"));
                } 
            echo $this->Form->submit('Next', array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_right",'onclick'=>"return Config.checkValidate();")); 
      } ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
	</div>
</div>
