	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>

	<?php if ($action=='edit') :?>
	<div class="clearfix">
	<?php
		echo $this->Form->input('student_id', [
			'label' => $this->Label->get('Users.add_student'),
			'onchange' => "$('#reload').val('add').click();",
            'options' => $attr['data']['studentOptions']
		]);
		?>
	</div>
	<?php endif;?>

	<div class="table-responsive">
		<table class="table table-curved table-checkable table-input">
			<thead>
				<tr>
					<th><?= $this->Label->get('General.openemis_no'); ?></th>
					<th><?= $this->Label->get('Users.name'); ?></th>
					<th><?= $this->Label->get('Users.gender_id'); ?></th>
					<th><?= $this->Label->get('Users.date_of_birth'); ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<tbody>
			<?php 
			foreach($attr['data']['students'] as $i => $obj) : 
				// pr($obj);die;
				if ($obj->status == 0) continue;

				if ($action=='edit') :
					$n = $obj->student_id;
					if (is_object($obj->user)) {
						$userData = [
							'openemis_no' => $obj->user->openemis_no,
							'name' => $obj->user->name,
							'gender' => ['name' => $obj->user->gender->name],
							'date_of_birth' => $ControllerAction['table']->formatDate($obj->user->date_of_birth)
						];
					} else if (is_array($obj->user)) {
						$userData = $obj->user;
					} else {
						/**
						 * @todo 
						 */
						$userData = false;
					}
			?>

				<tr>
					<?php

					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.id", [ 'value' => $obj->id ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.student_id", [ 'value' => $n ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.status", [ 'value' => $obj->status ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.institution_site_class_id", [ 'value' => $obj->institution_site_class_id ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.institution_site_section_id", [ 'value' => $obj->institution_site_section_id ]);

					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.user.id", [ 'value' => $n ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.user.openemis_no", [ 'value' => $userData['openemis_no'] ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.user.name", [ 'value' => $userData['name'] ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.user.gender.name", [ 'value' => $userData['gender']['name'] ]);
					echo $this->Form->hidden("InstitutionSiteClasses.institution_site_class_students.$n.user.date_of_birth", [ 'value' => $userData['date_of_birth'] ]);
					?>
					<td><?= $userData['openemis_no'] ?></td>
					<td><?= $userData['name'] ?></td>
					<td><?= $userData['gender']['name'] ?></td>
					<td><?= $userData['date_of_birth'] ?></td>
					<td> 
						<?php //if ($attr['data']['isHistoryRecord']): ?>
						
						<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">
							<?= __('<i class="fa fa-close"></i> Remove') ?>
						</button>
						
						<?php //else:?>
						
						<!-- <button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);$('#reload').val('add').click();">
							<?= __('<i class="fa fa-close"></i> Remove') ?>
						</button> -->
						
						<?php //endif;?>
					</td>
				</tr>

			<?php else:?>

				<tr>
					<td><?= $obj->user->openemis_no ?></td>
					<td><?= $obj->user->name ?></td>
					<td><?= $obj->user->gender->name ?></td>
					<td><?= $ControllerAction['table']->formatDate($obj->user->date_of_birth) ?></td>
				</tr>

			<?php endif;?>

		<?php endforeach ?>
				
			</tbody>
		</table>
	</div>
