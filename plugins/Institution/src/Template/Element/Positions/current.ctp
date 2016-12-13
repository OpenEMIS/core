<?php
	echo $this->Html->script('ControllerAction.../plugins/jasny/js/jasny-bootstrap.min', ['block' => true]);
?>

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
					<th><?= $this->Label->get('InstitutionStaff.fte'); ?></th>
				</tr>
			</thead>

			<tbody data-link="row">
				<?php
				// pr($ControllerAction['buttons']);die;
				// if $current is 0, we need to add an empty row so that the table header and table footer columns will have separator lines; else the lines will not show up.
				// Probably due to the existence of <tfoot> element.
				if (count($attr['data'])>0):
				?>
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
								<td><?= $obj->FTE ?></td>
							</tr>
						<?php endif; ?>
					<?php endforeach ?>

				<?php
				// Need to add an empty row so that an empty table will not look ugly without columns separator lines
				else:
				?>

				<tr></tr>

				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="3" class="side-label"><?php echo $this->Label->get('InstitutionStaff.total_fte'); ?></td>
					<td><?= $attr['totalCurrentFTE'];?></td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>
