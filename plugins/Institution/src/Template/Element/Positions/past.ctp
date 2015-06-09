<?php $_edit = (array_key_exists('edit', $_buttons) ? true : false);?>

	<div class="col-md-12">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th><?php echo $this->Label->get('date.start'); ?></th>
					<th><?php echo $this->Label->get('date.end'); ?></th>
					<th><?php echo $this->Label->get('general.status'); ?></th>
					<th><?php echo $this->Label->get('InstitutionSiteStaff.fte'); ?></th>
				</tr>
			</thead>
	
			<tbody>
				<?php if (count($attr['data'])>0) : ?>
				<?php foreach ($attr['data'] as $i => $obj) : ?>
				<tr>
					<td><?php echo $obj->user->openemis_no; ?></td>
					<td>
						<?php 
						if ($_edit) {
							echo $this->Html->link($obj->user->name, array('action' => $model, 'staffEdit', $obj->id));
						} else {
							echo $obj->user->name;
						}
						?>
					</td>
					<td><?php echo $table->formatDate($obj->start_date) ?></td>
					<td><?php echo $table->formatDate($obj->end_date) ?></td>
					<td><?php echo (is_object($obj->status) ? $obj->status->name : '') ?></td>
					<td><?php echo $obj->FTE ?></td>
				</tr>
				<?php endforeach ?>
				<?php endif ?>
			</tbody>
		</table>
	</div>
