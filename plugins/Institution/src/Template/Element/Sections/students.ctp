	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>

	<?php if ($action=='edit') :?>
	<div class="clearfix">
	<?php
		echo $this->Form->input('student_id', array(
			'options' => $attr['data']['studentOptions'],
			'label' => $this->Label->get('Users.add_student'),
			'onchange' => "$('#reload').val('add').click();"
		));
		?>
	</div>
	<?php endif;?>

	<div class="table-responsive">
		<table class="table table-curved">
			<thead>
				<tr>
					<th><?= $this->Label->get('General.openemis_no'); ?></th>
					<th><?= $this->Label->get('Users.name'); ?></th>
					<th><?= $this->Label->get('Users.gender_id'); ?></th>
					<th><?= $this->Label->get('Users.date_of_birth'); ?></th>
					<th><?= $this->Label->get($attr['model'] . '.education_grade'); ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<tbody>
			<?php 
			foreach($attr['data']['students'] as $i => $obj) : 
				if ($obj->status == 0) continue;

				if ($action=='edit') :
					$n = $obj->student_id;
			?>

				<tr>
					<?php

					echo $this->Form->hidden("InstitutionSections.institution_section_students.$n.id", [ 'value'=> $obj->id ]);
					echo $this->Form->hidden("InstitutionSections.institution_section_students.$n.student_id", [ 'value'=> $obj->student_id ]);
					echo $this->Form->hidden("InstitutionSections.institution_section_students.$n.status", [ 'value' => $obj->status ]);
					echo $this->Form->hidden("InstitutionSections.institution_section_students.$n.institution_section_id", [ 'value'=> $obj->institution_section_id ]);

					?>
					<td><?= $obj->user->openemis_no ?></td>
					<td><?= $obj->user->name ?></td>
					<td><?= $obj->user->gender->name ?></td>
					<td><?= $ControllerAction['table']->formatDate($obj->user->date_of_birth) ?></td>
					<td>
						<?php
						echo $this->Form->input("InstitutionSections.institution_section_students.$n.education_grade_id", array(
							'label' => false,
							'options' => $attr['data']['gradeOptions'],
							'value' => $obj->education_grade_id
						));
						?>
					</td>
					<td> 
						<!--<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemoveAndReload(this)">-->
						<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">
							<?= __('<i class="fa fa-close"></i> Remove') ?>
						</button>
					</td>
				</tr>

			<?php else:?>

				<tr>
					<td><?= $obj->user->openemis_no ?></td>
					<td><?= $obj->user->name ?></td>
					<td><?= $obj->user->gender->name ?></td>
					<td><?= $ControllerAction['table']->formatDate($obj->user->date_of_birth) ?></td>
					<td><?= (is_object($obj->education_grade) ? $obj->education_grade->name : ''); ?></td>
				</tr>

			<?php endif;?>

		<?php endforeach ?>
				
			</tbody>
		</table>
	</div>	
