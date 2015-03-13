<?php foreach ($comments as $key => $comment) : ?>
	<div class="row" style="border: solid 1px lightgrey;padding: 5px;">
		<b><?php echo $comment['CreatedUser']['first_name'] ." ". $comment['CreatedUser']['last_name']; ?></b>
		<?php echo __('added a comment'); ?>
		<span aria-hidden="true" class="glyphicon glyphicon-minus"></span>
		<i><?php echo $comment['WorkflowComment']['created']; ?></i>
		<!--span class="action_pullright">
			<a href="#" onclick="$('#reload').val('edit').click();return false;"><span aria-hidden="true" class="glyphicon glyphicon-pencil"></span></a>
		</span-->
		<br>
		<?php echo $comment['WorkflowComment']['comment']; ?>
	</div>
<?php endforeach ?>

<?php if (!empty($this->request->data['WorkflowComment']['workflow_log_id'])) : ?>
	<div class="row">
		<?php echo __('Comment'); ?>
		<br>
		<?php
			
			echo $this->Form->hidden('WorkflowComment.workflow_log_id');
			echo $this->Form->input('WorkflowComment.comment', array(
				'type' => 'textarea',
				'cols' => 60,
				'rows'=> 6,
				'label' => false,
				'div' => false,
				'before' => false,
				'between' => false,
				'after' => false
			));
		?>
	</div>

	<div class="row">
		<?php //echo $this->Form->hidden('WorkflowComment.id'); ?>
		<button type="submit" class="btn btn-default btn-sm" role="button" name="submit" value="add"><?php echo __('Add'); ?></button>
	</div>
<?php endif ?>
