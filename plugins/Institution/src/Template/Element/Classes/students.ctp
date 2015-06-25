	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></h3>

	<?php if ($action=='edit') :?>
	<div class="clearfix">
	<?php
		echo $this->Form->input('student_id', array(
			'options' => $attr['data']['studentOptions'],
			'label' => 'Add Student',
			'onchange' => "$('#reload').val('add').click();"
		));
		?>
	</div>
	<?php endif;?>

	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?php echo $this->Label->get('general.openemisId'); ?></th>
					<th><?php echo $this->Label->get('general.name'); ?></th>
					<th><?php echo $this->Label->get('general.gender'); ?></th>
					<th><?php echo $this->Label->get('general.date_of_birth'); ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<tbody>
			<?php 
			foreach($attr['data']['students'] as $i => $obj) : 
				if ($obj->status == 0) continue;

				if ($action=='edit') :
					$n = $obj->security_user_id;
			?>

				<tr>
					<?php

					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.id", [ 'value'=> $obj->id ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.security_user_id", [ 'value'=> $obj->security_user_id ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.status", [ 'value' => $obj->status ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.institution_site_class_id", [ 'value'=> $obj->institution_site_class_id ]);

					?>
					<td><?php echo $obj->user->openemis_no ?></td>
					<td><?php echo $obj->user->name ?></td>
					<td><?php echo $obj->user->gender->name ?></td>
					<td><?php echo $obj->user->date_of_birth ?></td>
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
					<td><?php echo $obj->user->gender->name ?></td>
					<td><?php echo $obj->user->date_of_birth ?></td>
				</tr>

			<?php endif;?>

		<?php endforeach ?>
				
			</tbody>
		</table>
</div>
