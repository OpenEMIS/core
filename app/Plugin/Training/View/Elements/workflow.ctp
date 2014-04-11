<?php if($_execute && $data['TrainingStatus']['id'] == 2 && $_approval){ ?>
<?php
echo $this->Form->create($modelName, array(
	'url' => array('controller' => $controller, 'action' => $approvalMethod.'Approval', 'plugin'=>$plugin),
	'type' => 'file',
	'inputDefaults' => array('label' => false, 'div' => false, 'class' => 'default', 'autocomplete' => 'off')
));
?>
<p>
<h1><?php echo __('Approval');?></h1>
    <?php echo $this->Form->input('WorkflowLog.model_name', array('type'=> 'hidden','value'=>$modelName));?>
	<?php echo $this->Form->input('WorkflowLog.record_id', array('type'=> 'hidden','value'=>$data[$modelName]['id']));?>
	<?php echo $this->Form->input('WorkflowLog.workflow_step_id', array('type'=>'hidden','value'=>$workflowStepId));?>
	<?php echo $this->Form->input('WorkflowLog.step', array('type'=>'hidden','value'=>$workflowStep));?>
 	<div class="row">
        <div class="label"><?php echo __('Comments');?></div>
        <div class="value"><?php echo $this->Form->input('WorkflowLog.comments', array('type'=>'textarea', 'label'=>false));  ?></div>
    </div>
    <div class="controls view_controls">
		<input type="submit" value="<?php echo __("Approve"); ?>" name='approve' class="btn_save btn_right" />
		<input type="submit" value="<?php echo __("Reject"); ?>" name='reject' class="btn_save btn_right" />
	</div>
</p>
<?php echo $this->Form->end(); ?>
<?php } ?>
<?php if ($_viewApprovalLog){
	if(!empty($workflowLogs)){ ?>
		<p>
		<div class="table full_width">
        <div class="table_head">
       		<div class="table_cell"><?php echo __('User'); ?></div>
            <div class="table_cell"><?php echo __('Action'); ?></div>
            <div class="table_cell"><?php echo __('Comments'); ?></div>
            <div class="table_cell"><?php echo __('Date'); ?></div>
        </div>
        <div class="table_body">
		<?php foreach($workflowLogs as $val){?>
            <div class="table_row">
            	<div class="table_cell"><?php echo $val['SecurityUser']['first_name']; ?>, <?php echo $val['SecurityUser']['last_name']; ?></div>
                <div class="table_cell"><?php echo ($val['WorkflowLog']['approve']==1) ? __('Approved') : __('Rejected'); ?></div>
                <div class="table_cell"><?php echo $val['WorkflowLog']['comments']; ?></div>
                <div class="table_cell"><?php echo $val['WorkflowLog']['created']; ?></div>
                </div>
           <?php } ?>
        </div>
        </div>
    	</p>
	<?php
	}

}

?>