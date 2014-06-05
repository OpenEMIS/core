<?php /*
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('attachments', false);
?>

<?php echo $this->Html->script('/Staff/js/training_self_studies', false); ?>

<?php echo $this->element('breadcrumb'); ?>

<div id="training_self_study" class="content_wrapper edit add">
	<h1>
		<span><?php echo __($subheader); ?></span>
		<?php
		
            echo $this->Html->link(__('Back'), array('action' => 'trainingSelfStudy'), array('class' => 'divider'));
        
		?>
	</h1>
	
	<?php
	echo $this->Form->create($modelName, array(
		'url' => array('controller' => 'Staff', 'action' => 'trainingSelfStudyAdd', 'plugin'=>'Staff'),
		'type' => 'file',
		'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
	));
	?>

	<?php if(!empty($this->data[$modelName]['id'])){ echo $this->Form->input('id', array('type'=> 'hidden')); } ?>
	<?php if(!empty($this->data[$modelName]['training_status_id'])){ echo $this->Form->input('training_status_id', array('type'=> 'hidden')); } ?>
   	<div class="row">
		<div class="label"><?php echo __('Course Title'); ?></div>
		<div class="value"><?php echo $this->Form->input('title');?></div>
	</div>
 	<div class="row">
        <div class="label"><?php echo __('Start Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('start_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
 	</div>   
    <div class="row">
         <div class="label"><?php echo __('End Date'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('end_date', array('type' => 'date', 'dateFormat' => 'DMY', 'before' => '<div class="left">', 'after' => '</div>','class'=>false)); 
		?>
        </div>
    </div>
	<div class="row">
		<div class="label"><?php echo __('Course Description'); ?></div>
		<div class="value"><?php echo $this->Form->input('description', array('type'=>'textarea'));?></div>
	</div>
	<div class="row">
        <div class="label"><?php echo __('Goal / Objectives'); ?></div>
        <div class="value">
		<?php 
			echo $this->Form->input('objective', array('type'=>'textarea')); 
		?>
        </div>
    </div>
      <div class="row">
        <div class="label"><?php echo __('Location'); ?></div>
        <div class="value">
 			<?php echo $this->Form->input('location');?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Provider'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('training_provider_id', array('options'=>$trainingProviderOptions, 'empty'=>'--Select--'));?>
        </div>
    </div>
  	<div class="row">
        <div class="label"><?php echo __('Hours'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('hours');?>
        </div>
    </div>
    <div class="row">
        <div class="label"><?php echo __('Credits'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('credit_hours', array('options'=>$trainingCreditHourOptions));?>
        </div>
    </div>
     <div class="row">
        <div class="label"><?php echo __('Result'); ?></div>
        <div class="value">
        	<?php echo $this->Form->input('result');?>
        </div>
    </div>
	<div class="row">
		<div class="label"><?php echo __('Completed'); ?></div>
		<div class="value">
			<?php echo $this->Form->input('pass', array('options' => array('1'=>__('Passed'), '0' => 'Failed')));?>
		</div>
	</div>
    <span id="controller" class="none"><?php echo $this->params['controller']; ?></span>

	<div class="row">
		<div class="label"><?php echo __('Attachments'); ?></div>
		<div class="value">
		<div class="table file_upload" style="width:240px;">
			<div class="table_body">
				<?php
				if(isset($attachments) && !empty($attachments)){
				foreach($attachments as $index => $value){
					$obj = $value[$_model];
					$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
					$fieldName = sprintf('data[%s][%s][%%s]', $_model, $index);
				?>
				
				<div class="table_row" file-id="<?php echo $obj['id']; ?>">
					<?php echo $this->Utility->getIdInput($this->Form, $fieldName, $obj['id']); ?>
					<?php echo $this->Form->input('name', array('type'=>'hidden','name' => sprintf($fieldName, 'name'), 'value' => $obj['name'])); ?>
					<?php
					echo $this->Form->input('description', array('type'=>'hidden',
						'name' => sprintf($fieldName, 'description'),
						'value' => $obj['description']
					));
					?>
					<div class="table_cell center"><?php echo $this->Html->link($obj['name'], array('action' => 'attachmentsTrainingSelfStudyDownload', $obj['id']));?></div>
					<?php if($_delete) { ?>
					<div class="table_cell cell_delete">
						<span class="icon_delete" title="<?php echo __("Delete"); ?>" onClick="objTrainingSelfStudies.deleteFile(<?php echo $obj['id']; ?>)"></span>
					</div>
					<?php } ?>
				</div>
				<?php 	
					}
				}	
				?>
			</div>
		</div>
		 <div style="color:#666666;font-size:10px;"><?php echo __('Note: Max upload file size is 2MB.'); ?></div> 
		<?php echo $this->Utility->getAddRow('Attachment');?>
		</div>
	</div>
	<div class="controls view_controls">
		<?php if(!isset($this->request->data['TeacherTrainingSelfStudy']['training_status_id'])|| $this->request->data['TeacherTrainingSelfStudy']['training_status_id']==1){ ?>
		<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="js:if(objTrainingSelfStudies.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
		<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="js:if(objTrainingSelfStudies.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
		<?php } ?>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'trainingSelfStudy'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
	
	<?php echo $this->Form->end(); ?>
</div>
 * 
 */ ?>

<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);
echo $this->Html->css('../js/plugins/datepicker/css/datepicker', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/datepicker/js/bootstrap-datepicker', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');

$startDate = array('id' => 'startDate');
$endDate = array('id' => 'endDate');
if(!empty($this->data[$model]['id'])){
	$redirectAction = array('action' => 'trainingSelfStudyView', $this->data[$model]['id']);
	$startDate['data-date'] = $this->data[$model]['start_date'];
    $endDate['data-date'] = $this->data[$model]['end_date'];
}
else{
	$redirectAction = array('action' => 'trainingSelfStudy');
    $endDate['data-date'] =  date('d-m-Y', time() + 86400);
}
echo $this->Html->link($this->Label->get('general.back'), $redirectAction, array('class' => 'divider'));

$this->end();
$this->start('contentBody');
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $this->action, 'plugin'=>'Staff'));
$formOptions['type'] = 'file';
$formOptions['deleteUrl']=$this->params['controller']."/trainingSelfStudyAttachmentsDelete/";
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->hidden('training_status_id');
echo $this->Form->input('title');
echo $this->FormUtility->datepicker('start_date', $startDate);
echo $this->FormUtility->datepicker('end_date', $endDate);
echo $this->Form->input('description', array('type'=>'textarea'));
echo $this->Form->input('objective', array('type'=>'textarea')); 
echo $this->Form->input('location');
echo $this->Form->input('training_provider_id', array('options'=>$trainingProviderOptions));
echo $this->Form->input('hours');
echo $this->Form->input('credit_hours', array('options'=>$trainingCreditHourOptions));
echo $this->Form->input('result');
echo $this->Form->input('pass', array('options' => $passfailOptions));

$multiple = array('multipleURL' => $this->params['controller']."/trainingSelfStudyAjaxAddField/");
echo $this->Form->hidden('maxFileSize', array('name'=> 'MAX_FILE_SIZE','value'=>(2*1024*1024)));
echo $this->element('templates/file_upload', compact('multiple'));

$tableHeaders = array(__('File(s)'), '&nbsp;');
$tableData = array();
foreach ($attachments as $obj) {
	$row = array();
	$row[] = array($obj['StaffTrainingSelfStudyAttachment']['file_name'], array('file-id' =>$obj['StaffTrainingSelfStudyAttachment']['id']));
	$row[] = '<span class="icon_delete" title="'. $this->Label->get('general.delete').'" onClick="jsForm.deleteFile('.$obj['StaffTrainingSelfStudyAttachment']['id'].')"></span>';
	$tableData[] = $row;
}
echo $this->element('templates/file_list', compact('tableHeaders', 'tableData'));

?>
<div class="form-group">
	<div class="col-md-offset-4">
		<?php if (!isset($this->request->data['StaffTrainingSelfStudy']['training_status_id']) || $this->request->data['StaffTrainingSelfStudy']['training_status_id'] == 1) { ?>
			<input type="submit" value="<?php echo __("Save"); ?>" name='save' class="btn_save btn_right" onclick="js:if(objTrainingSelfStudies.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
			<input type="submit" value="<?php echo __("Submit for Approval"); ?>" name='submitForApproval' class="btn_save btn_right" onclick="js:if(objTrainingSelfStudies.errorFlag() && Config.checkValidate()){ return true; }else{ return false; }"/>
		<?php } ?>
		<?php echo $this->Html->link(__('Cancel'), array('action' => 'trainingSelfStudy'), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>
<?php
echo $this->Form->end();
$this->end();
?>