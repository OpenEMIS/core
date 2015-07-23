<?php $_edit = (array_key_exists('edit', $ControllerAction['buttons']) ? true : false);?>
	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>

	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('Users.openemis_no'); ?></th>
					<th><?= $this->Label->get('Users.name'); ?></th>
					<th><?= $this->Label->get('date.start'); ?></th>
					<th><?= $this->Label->get('date.end'); ?></th>
					<th><?= $this->Label->get('Users.status'); ?></th>
					<th><?= $this->Label->get('InstitutionSiteStaff.fte'); ?></th>
				</tr>
			</thead>
	
			<tbody data-link="row">
				<?php if (count($attr['data'])>0) : ?>
					<?php foreach ($attr['data'] as $i => $obj) : ?>
						<?php if (!is_object($obj->user)): ?>
							<tr><td>There is an error with this user data. User might have been deleted from users table.</td></tr>
							<?php else: 
								$link = '';
								if ($_edit) {
									$url = $ControllerAction['buttons']['index']['url'];
									$url['action'] = 'StaffPositions';
									$url[0] = 'view';
									$url[1] = $obj->id;
									$link = $this->Url->build($url, true);
								} 
							?>
							<tr onclick="location.href='<?= $link ?>'">
								<td><?= $obj->user->openemis_no; ?></td>
								<td><?= $obj->user->name ?></td>
								<td><?= $ControllerAction['table']->formatDate($obj->start_date) ?></td>
								<td><?= $ControllerAction['table']->formatDate($obj->end_date) ?></td>
								<td><?= (is_object($obj->staff_status) ? $obj->staff_status->name : '') ?></td>
								<td><?= $obj->FTE ?></td>
							</tr>
						<?php endif ?>
					<?php endforeach ?>
				<?php endif ?>
			</tbody>
		</table>
	</div>
