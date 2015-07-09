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
					<th><?php echo $this->Label->get('InstitutionSiteStaff.fte'); ?></th>
				</tr>
			</thead>
	
			<tbody>
				<?php 
				// pr($ControllerAction['buttons']);die;
				// if $current is 0, we need to add an empty row so that the table header and table footer columns will have separator lines; else the lines will not show up.
				// Probably due to the existence of <tfoot> element.
				if (count($attr['data'])>0): 
				?>

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
								<td><?php echo $obj->FTE ?></td>
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
					<td colspan="3" class="side-label"><?php echo $this->Label->get('InstitutionSiteStaff.total_fte'); ?></td>
					<td><?php echo $attr['totalCurrentFTE'];?></td>
				</tr>
			</tfoot>
		</table>
	</div>
