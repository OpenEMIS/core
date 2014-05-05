<?php if($_execute && $data['TrainingStatus']['id'] == 2 && $_approval){ ?>
<?php
$formOptions = $this->FormUtility->getFormOptions(array('controller' => $controller, 'action' => $approvalMethod.'Approval'), 'file');
echo $this->Form->create($modelName, $formOptions);
?>
<p>
<h1><?php echo __('Approval');?></h1>

    <?php echo $this->Form->input('WorkflowLog.model_name', array('type'=> 'hidden','value'=>$modelName));?>
	<?php echo $this->Form->input('WorkflowLog.record_id', array('type'=> 'hidden','value'=>$data[$modelName]['id']));?>
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
	if(!empty($workflowLogs)){ ?>
    <p>
		<div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
        <thead class="table_head">
            <tr>
           		<td class="table_cell"><?php echo __('User'); ?></td>
                <td class="table_cell"><?php echo __('Action'); ?></td>
                <td class="table_cell"><?php echo __('Comments'); ?></td>
                <td class="table_cell"><?php echo __('Date'); ?></td>
            </tr>
        </thead>
        <tbody class="table_body">
		<?php foreach($workflowLogs as $val){?>
            <tr class="table_row">
            	<td class="table_cell"><?php echo $val['SecurityUser']['first_name']; ?>, <?php echo $val['SecurityUser']['last_name']; ?></td>
                <td class="table_cell"><?php echo ($val['WorkflowLog']['approve']==1) ? __('Approved') : __('Rejected'); ?></td>
                <td class="table_cell"><?php echo $val['WorkflowLog']['comments']; ?></td>
                <td class="table_cell"><?php echo $val['WorkflowLog']['created']; ?></td>
            </tr>
           <?php } ?>
        </tbody>
        </table>
        </div>
    </p>
	<?php
	}

}

?>
