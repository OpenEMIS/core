	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>

	<?php if ($action=='edit') :?>
	<div class="clearfix">
	<?php
		echo $this->Form->input('staff_id', array(
			'options' => $attr['data']['teacherOptions'],
			'label' => $this->Label->get('Users.add_teacher'),
			'onchange' => "$('#reload').val('add').click();"
		));
		?>
	</div>
	<?php endif;?>

	<div class="table-in-view">
		<table class="table table-checkable table-input">
			<thead>
				<tr>
					<th><?= $this->Label->get('General.openemis_no'); ?></th>
					<th><?= $this->Label->get('Users.name'); ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<tbody>
			<?php 
			foreach($attr['data']['teachers'] as $i => $obj) : 
				if ($obj->status == 0) continue;

				if ($action=='edit') :
					$n = $obj->security_user_id;
			?>

				<tr>
					<?php

					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_staff.$n.id", [ 'value'=> $obj->id ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_staff.$n.security_user_id", [ 'value'=> $obj->security_user_id ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_staff.$n.status", [ 'value' => $obj->status ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_staff.$n.institution_site_class_id", [ 'value'=> $obj->institution_site_class_id ]);

					?>
					<td><?php echo $obj->user->openemis_no ?></td>
					<td><?php echo $obj->user->name ?></td>
					<td> 
						<!--<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemoveAndReload(this)">-->
						<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);$('#reload').val('add').click();">
							<?= __('<i class="fa fa-close"></i> Remove') ?>
						</button>
					</td>
				</tr>

			<?php else:?>

				<tr>
					<td><?php echo $obj->user->openemis_no ?></td>
					<td><?php echo $obj->user->name ?></td>
				</tr>

			<?php endif;?>

		<?php endforeach ?>
				
			</tbody>
		</table>
	</div>
