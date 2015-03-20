<div class="row">
	<?php echo $this->Form->input('WorkflowCommentEdit.createdby', array('data-field' => 'createdby', 'label' => __('Created By'), 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'readonly' => 'readonly')); ?>
</div>
<div class="row">
	<?php echo $this->Form->input('WorkflowCommentEdit.createdon', array('data-field' => 'createdon', 'label' => __('Created On'), 'div' => false, 'before' => false, 'between' => false, 'after' => false, 'readonly' => 'readonly')); ?>
</div>
<div class="row">
	<?php
		echo $this->Form->hidden('WorkflowCommentEdit.id', array('data-field' => 'id'));
		echo $this->Form->input('WorkflowCommentEdit.comment', array(
			'data-field' => 'comment',
			'type' => 'textarea',
			'cols' => 60,
			'rows'=> 6,
			'label' => __('Comment'),
			'div' => false,
			'before' => false,
			'between' => false,
			'after' => false
		));
	?>
</div>
