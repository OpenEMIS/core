<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('attachments', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="attachments" class="content_wrapper">
	<?php
	echo $this->Form->create($this->params['controller'], array(
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false), 
		'url' => array('plugin' => $this->params['plugin'], 'controller' => $this->params['controller'], 'action' => 'attachmentsEdit')
	));
	?>
	<h1>
		<span><?php echo __('Attachments'); ?></span>
		<?php 
		if(!isset($WizardMode) || !$WizardMode){ ?>
		<?php echo $this->Html->link(__('View'), array('action' => 'attachments'), array('class' => 'divider')); ?>
		<?php } ?>
	</h1>
	
	<?php echo $this->element('alert'); ?>
	
	<span id="controller" class="none"><?php echo $this->params['controller']; ?></span>
		
	<div class="table full_width" style="margin-bottom: -1px;">
		<div class="table_head">
			<div class="table_cell cell_file"><?php echo __('File'); ?></div>
			<div class="table_cell cell_description"><?php echo __('Description'); ?></div>
			<div class="table_cell"><?php echo __('File Type'); ?></div>
			<div class="table_cell cell_date"><?php echo __('Uploaded On'); ?></div>
			<?php if($_delete) { ?>
				<div class="table_cell cell_delete">&nbsp;</div>
			<?php } ?>
		</div>
					
		<div class="table_body">
			<?php
			foreach($data as $index => $value){
				$obj = $value[$_model];
				$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
				$fieldName = sprintf('data[%s][%s][%%s]', $_model, $index);
			?>
			
			<div class="table_row" file-id="<?php echo $obj['id']; ?>">
				<?php echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']); ?>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php echo $this->Form->input('name', array('name' => sprintf($fieldName, 'name'), 'value' => $obj['name'])); ?>
					</div>
				</div>
				<div class="table_cell">
					<div class="input_wrapper">
					<?php
					echo $this->Form->textarea('description', array(
						'name' => sprintf($fieldName, 'description'),
						'value' => $obj['description']
					));
					?>
					</div>
				</div>
				<div class="table_cell center"><?php echo array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext; ?></div>
				<div class="table_cell center"><?php echo $obj['created']; ?></div>
				<?php if($_delete) { ?>
				<div class="table_cell cell_delete">
					<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="attachments.deleteFile(<?php echo $obj['id']; ?>)"></span>
				</div>
				<?php } ?>
			</div>
			<?php }	?>
		</div>
	</div>
	
	<div class="table full_width file_upload">
		<div class="table_body"></div>
	</div>
	
	<?php if($_add) { echo $this->Utility->getAddRow('Attachment'); } ?>
	
    <div class="controls">
        <div style="position:absolute;float:left;color:#666666;font-size:10px;"><?php echo __('Note: Max upload file size is 2MB.'); ?></div> 
        <?php if(!isset($WizardMode) || !$WizardMode){ ?>
        <input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'attachments'), array('class' => 'btn_cancel btn_left')); ?>
        <?php }else{?>
            <?php 
            echo $this->Form->submit(__('Previous'), array('div'=>false, 'name'=>'submit','class'=>"btn_save btn_cancel_button btn_right"));              
                if($mandatory!='1'){
                echo $this->Form->submit(__('Skip'), array('div'=>false, 'name'=>'submit','class'=>"btn_cancel btn_cancel_button btn_right"));
                } 
            if(!$wizardEnd){
                    echo $this->Form->submit(__('Next'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
               }else{
                    echo $this->Form->submit(__('Finish'), array('div'=>false, 'name'=>'submit', 'name'=>'submit','class'=>"btn_save btn_left",'onclick'=>"return Config.checkValidate();")); 
                }
      } ?>
	</div>
</div>
