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
		<table class="table table-striped table-hover table-bordered">
			<thead>
				<tr>
					<th><?= $this->Label->get('Users.openemis_no'); ?></th>
					<th><?= $this->Label->get('Users.name'); ?></th>
					<th><?= $this->Label->get('Users.gender'); ?></th>
					<th><?= $this->Label->get('Users.date_of_birth'); ?></th>
					<th><?= $this->Label->get($attr['model'] . '.education_grade'); ?></th>
					<th><?= $this->Label->get('Users.student_category'); ?></th>
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

					echo $this->Form->hidden("InstitutionSiteSections.institution_site_section_students.$n.id", [ 'value'=> $obj->id ]);
					echo $this->Form->hidden("InstitutionSiteSections.institution_site_section_students.$n.security_user_id", [ 'value'=> $obj->security_user_id ]);
					echo $this->Form->hidden("InstitutionSiteSections.institution_site_section_students.$n.status", [ 'value' => $obj->status ]);
					echo $this->Form->hidden("InstitutionSiteSections.institution_site_section_students.$n.institution_site_section_id", [ 'value'=> $obj->institution_site_section_id ]);

					?>
					<td><?php echo $obj->user->openemis_no ?></td>
					<td><?php echo $obj->user->name ?></td>
					<td><?php echo $obj->user->gender->name ?></td>
					<td><?php echo $obj->user->date_of_birth_formatted ?></td>
					<td>
						<?php
						echo $this->Form->input("InstitutionSiteSections.institution_site_section_students.$n.education_grade_id", array(
							'label' => false,
							'options' => $attr['data']['gradeOptions'],
							'value' => $obj->education_grade_id
						));
						?>
					</td>
					<td>
						<?php
						echo $this->Form->input("InstitutionSiteSections.institution_site_section_students.$n.student_category_id", array(
							'label' => false,
							'options' => $attr['data']['categoryOptions'],
							'value' => $obj->student_category_id
						));
						?>
					</td>
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
					<td><?php echo $obj->user->date_of_birth_formatted ?></td>
					<td><?php echo (is_object($obj->education_grade) ? $obj->education_grade->name : ''); ?></td>
					<td><?php echo $attr['data']['categoryOptions'][$obj->student_category_id] ?></td>
				</tr>

			<?php endif;?>

		<?php endforeach ?>
				
			</tbody>
		</table>
	</div>	
