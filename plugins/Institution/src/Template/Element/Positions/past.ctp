<div class="clearfix"></div>

<hr>

<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>
<div class="table-wrapper">
	<div class="table-responsive">
		<table class="table table-curved">
			<thead>
				<tr>
					<th><?= $this->Label->get('General.openemis_no'); ?></th>
					<th><?= $this->Label->get('Users.name'); ?></th>
					<th><?= $this->Label->get('date.start'); ?></th>
					<th><?= $this->Label->get('date.end'); ?></th>
					<th><?= $this->Label->get('Users.status'); ?></th>
					<th><?= $this->Label->get('InstitutionStaff.fte'); ?></th>
				</tr>
			</thead>

			<tbody data-link="row">
				<?php if (count($attr['data'])>0) : ?>
					<?php foreach ($attr['data'] as $i => $obj) : ?>
						<?php if (!is_object($obj->user)): ?>
							<tr><td>There is an error with this user data. User might have been deleted from users table.</td></tr>
							<?php else:
								$link = '';
							?>
							<tr>
								<td><?= $this->html->link($obj->user->openemis_no, [
										'plugin' => 'Institution',
										'controller' => 'Institutions',
										'action' => 'StaffUser',
										'view',
										$this->ControllerAction->paramsEncode(['id' => $obj->user->id])
									]) ?>
								</td>
								<td><?= $obj->user->name ?></td>
								<td><?= $ControllerAction['table']->formatDate($obj->start_date) ?></td>
								<td><?= $ControllerAction['table']->formatDate($obj->end_date) ?></td>
								<td><?php
									$staffStatus = is_object($obj->staff_status) ? $obj->staff_status->name : '';
									echo __($staffStatus);
								 ?></td>
								<td><?= $obj->FTE ?></td>
							</tr>
						<?php endif ?>
					<?php endforeach ?>
				<?php endif ?>
			</tbody>
		</table>
	</div>
</div>
