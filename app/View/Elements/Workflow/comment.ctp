<?php foreach ($comments as $key => $comment) : ?>
	<div class="row" style="border: solid 1px lightgrey;padding: 5px;" row-id="<?php echo $comment['WorkflowComment']['id'] ?>">
		<?php if (!empty($comment['WorkflowComment']['modified'])) : ?>
			<b><?php echo $comment['ModifiedUser']['first_name'] ." ". $comment['ModifiedUser']['last_name']; ?></b>
			<?php echo __('make changes'); ?>
			<span aria-hidden="true" class="glyphicon glyphicon-minus"></span>
			<i><?php echo $comment['WorkflowComment']['modified']; ?></i>
		<?php else : ?>
			<b><?php echo $comment['CreatedUser']['first_name'] ." ". $comment['CreatedUser']['last_name']; ?></b>
			<?php echo __('added a comment'); ?>
			<span aria-hidden="true" class="glyphicon glyphicon-minus"></span>
			<i><?php echo $comment['WorkflowComment']['created']; ?></i>
		<?php endif ?>
		<span class="action_pullright">
			<?php if ($comment['WorkflowComment']['created_user_id'] == $userId) : ?>
				<?php
					echo $this->Form->hidden('id', array('data-field' => 'id', 'value' => $comment['WorkflowComment']['id'], 'disabled' => 'disabled'));
					echo $this->Form->hidden('createdby', array('data-field' => 'createdby', 'value' => $comment['CreatedUser']['first_name'] . " " . $comment['CreatedUser']['last_name'], 'disabled' => 'disabled'));
					echo $this->Form->hidden('createdon', array('data-field' => 'createdon', 'value' => $comment['WorkflowComment']['created'], 'disabled' => 'disabled'));
					echo $this->Form->hidden('comment', array('data-field' => 'comment', 'value' => $comment['WorkflowComment']['comment'], 'disabled' => 'disabled'));
				?>
				<a href="#" onclick="jsForm.doCopy($('[row-id=<?php echo $comment['WorkflowComment']['id']; ?>]'), $('#editComment'))" data-toggle="modal" data-target="#editComment"><span aria-hidden="true" class="glyphicon glyphicon-pencil"></span></a>
				<a href="#" onclick="$('#WorkflowCommentId').val('<?php echo $comment['WorkflowComment']['id']; ?>');$('#reload').val('delete').click();return false;"><span aria-hidden="true" class="glyphicon glyphicon-trash"></span></a>
			<?php endif ?>
		</span>
		<br>
		<?php echo $comment['WorkflowComment']['comment']; ?>
	</div>
<?php endforeach ?>

<div class="modal fade" id="editComment" tabindex="-1" role="dialog" aria-hidden="true" style="padding-top: 150px;">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo __('Edit Comment');?></h4>
			</div>
			<div class="modal-body">
				<?php echo $this->element('Workflow/comment_edit'); ?>
			</div>
			<div class="modal-footer">
				<button type="submit" class="btn btn-primary btn-sm" role="button" name="submit" value="edit"><?php echo __('Save'); ?></button>
				<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<?php
		echo $this->Form->input('WorkflowComment.comment', array(
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

<div class="row">
	<?php echo $this->Form->hidden('WorkflowComment.id'); ?>
	<?php echo $this->Form->hidden('WorkflowComment.workflow_record_id'); ?>
	<button type="submit" class="btn btn-primary btn-sm" role="button" name="submit" value="add"><?php echo __('Add'); ?></button>
</div>

<?php if (!empty($this->request->data['WorkflowComment']['workflow_log_id'])) : ?>
<?php endif ?>
