<?php if($_execute && $data['TrainingStatus']['id'] == 2 && $_approval){ ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $controller, 'action' => $approvalMethod.'Approval'), 'file');
echo $this->Form->create($model, $formOptions);
?>
<p>
<h1><?php echo __('Approval');?></h1>

    <?php echo $this->Form->input('WorkflowLog.model_name', array('type'=> 'hidden','value'=>$model));?>
	<?php echo $this->Form->input('WorkflowLog.record_id', array('type'=> 'hidden','value'=>$data[$model]['id']));?>
	<?php echo $this->Form->input('WorkflowLog.workflow_step_id', array('type'=>'hidden','value'=>$workflowStepId));?>
	<?php echo $this->Form->input('WorkflowLog.step', array('type'=>'hidden','value'=>$workflowStep));?>
 	<?php echo $this->Form->input('WorkflowLog.comments', array('type'=>'textarea'));  ?>

    <div class="controls view_controls center">
		<input type="submit" value="<?php echo __("Approve"); ?>" name='approve' class="btn_save btn_right" />
		<input type="submit" value="<?php echo __("Reject"); ?>" name='reject' class="btn_save btn_right" />
	</div>
</p>

<?php echo $this->Form->end(); ?>
<?php } ?>
<?php if ($_viewApprovalLog){
	if(!empty($workflowLogs)){ 
		$tableHeaders = array(__('User'), __('Action'), __('Comments'), __('Date'));
		$tableData = array();
		foreach ($workflowLogs as $obj) {
			$row = array();
			$row[] = $obj['SecurityUser']['first_name'].", ".$obj['SecurityUser']['last_name'];
			$row[] = ($obj['WorkflowLog']['approve']==1) ? __('Approved') : __('Rejected');
			$row[] = $obj['WorkflowLog']['comments'];
			$row[] = $obj['WorkflowLog']['created'];
			$tableData[] = $row;
		}
		echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
	}
	
}
?>
   