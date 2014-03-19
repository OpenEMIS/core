<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->script('/Staff/js/licenses', false);
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->css('jquery-ui.min', 'stylesheet', array('inline' => false));
echo $this->Html->script('jquery-ui.min', false);?>

<?php echo $this->element('breadcrumb'); ?>

<div id="license" class="content_wrapper edit add" url="Staff/ajax_find_license/" >
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
            if (!$WizardMode) {
	            if(!empty($this->data[$modelName]['id'])){
     			 	echo $this->Html->link(__('Back'), array('action' => 'licenseView',$this->data[$modelName]['id']), array('class' => 'divider'));
		       	}else{
	        	 	echo $this->Html->link(__('Back'), array('action' => 'license'), array('class' => 'divider'));
		        }
	        }
		?>
	</h1>
	
    <?php echo $this->element('alert'); ?>
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Staff', 'action' => 'licenseAdd', 'plugin'=>'Staff'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<div class="row">
        <div class="label"><?php echo __('Issue Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('issue_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Type'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('license_type_id', array(
									'options' => $licenseTypeOptions,
									'label' => false)
									); 
		?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Issuer'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('issuer', array('id' => 'searchIssuer', 'class'=>'default issuer', 'placeholder' => __('Issuer')));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Number'); ?></div>
        <div class="value"><?php echo $this->Form->input('license_number');?></div>
    </div>
	<div class="row">
        <div class="label"><?php echo __('Expiry Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('expiry_date', array('type' => 'date', 'empty'=>'Select', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
	<?php if($WizardMode){ ?>
    <div class="view_controls">
        <?php echo $this->Form->submit(__('Add More'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_cancel_button btn_right")); ?>
    </div>
    <?php } ?>
	<div class="controls">
		<?php if(!$WizardMode){ ?>
        <input type="submit" value="<?php echo __("Save"); ?>" class="btn_save btn_right" onclick="return Config.checkValidate();"/>
	        <?php if(!empty($this->data[$modelName]['id'])){?>
	        <?php echo $this->Html->link(__('Cancel'), array('action' => 'licenseView',$this->data[$modelName]['id']), array('class' => 'btn_cancel btn_left')); ?>
	        <?php }else{ ?>
	         <?php echo $this->Html->link(__('Cancel'), array('action' => 'license'), array('class' => 'btn_cancel btn_left')); ?>
	        <?php } ?>
        <?php }else{?>
            <?php 
                echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_cancel_button btn_right"));
                if($mandatory!='1'){
	                echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_right"));
                } 
	            echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
      	} ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>