<div class="row">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Transition'); ?></th>
				<th><?php echo __('Last Executer'); ?></th>
				<th><?php echo __('Last Execution Date'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($transitions as $key => $transition) : ?>
				<tr>
					<td>
						<?php echo $transition['PrevWorkflowStep']['name']; ?>
						<span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span>
						<?php echo $transition['WfWorkflowStep']['name']; ?>
					</td>
					<td><?php echo $transition['CreatedUser']['first_name'] ." ". $transition['CreatedUser']['last_name']; ?></td>
					<td><?php echo $transition['WorkflowTransition']['created']; ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
