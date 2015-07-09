<?php $_edit = (array_key_exists('edit', $ControllerAction['buttons']) ? true : false);?>
	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>

	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('Users.openemis_no'); ?></th>
					<th><?php echo $this->Label->get('Users.name'); ?></th>
					<th><?php echo $this->Label->get('date.start'); ?></th>
					<th><?php echo $this->Label->get('date.end'); ?></th>
					<th><?php echo $this->Label->get('Users.status'); ?></th>
					<th><?php echo $this->Label->get('InstitutionSiteStaff.fte'); ?></th>
				</tr>
			</thead>
	
			<tbody>
				<?php if (count($attr['data'])>0) : ?>
					<?php foreach ($attr['data'] as $i => $obj) : ?>
						<?php if (!is_object($obj->user)): ?>
							<tr><td>There is an error with this user data. User might have been deleted from users table.</td></tr>
						<?php else: ?>
							<tr>
								<td><?php echo $obj->user->openemis_no; ?></td>
								<td>
									<?php 
									if ($_edit) {
										$url = $ControllerAction['buttons']['index']['url'];
										$url['action'] = 'Staff';
										$url[0] = 'edit';
										$url[1] = $obj->id;
										echo $this->Html->link($obj->user->name, $url);
									} else {
										echo $obj->user->name;
									}
									?>
								</td>
								<td><?php echo $ControllerAction['table']->formatDate($obj->start_date) ?></td>
								<td><?php echo $ControllerAction['table']->formatDate($obj->end_date) ?></td>
								<td><?php echo (is_object($obj->staff_status) ? $obj->staff_status->name : '') ?></td>
								<td><?php echo $obj->FTE ?></td>
							</tr>
						<?php endif ?>
					<?php endforeach ?>
				<?php endif ?>
			</tbody>
		</table>
	</div>
