	<div class="clearfix"></div>

	<hr>

	<h3><?php echo __('Students') ?></label></h3>

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
					<th><?php echo $this->Label->get('general.education_grade'); ?></th>
					<th><?php echo $this->Label->get('general.category'); ?></th>
					<th class="cell-delete"></th>
				</tr>
			</thead>

			<tbody>
			<?php 
			foreach($attr['data']['students'] as $i => $obj) : 
				if ($obj->status == 0) continue;

				if ($action=='edit') :
			?>

				<tr>
					<?php

					echo $this->Form->hidden("institution_site_section_students.$i.id", [ 'value'=> $obj->id ]);
					echo $this->Form->hidden("institution_site_section_students.$i.security_user_id", [ 'value'=> $obj->security_user_id ]);
					echo $this->Form->hidden("institution_site_section_students.$i.status", [ 'value' => $obj->status ]);
					echo $this->Form->hidden("institution_site_section_students.$i.institution_site_section_id", [ 'value'=> $obj->institution_site_section_id ]);

					?>
					<td><?php echo $obj->user->openemis_no ?></td>
					<td><?php echo $obj->user->name ?></td>
					<td><?php echo $obj->user->gender->name ?></td>
					<td><?php echo $obj->user->date_of_birth ?></td>
					<td>
						<?php
						echo $this->Form->input("institution_site_section_students.$i.education_grade_id", array(
							'label' => false,
							'options' => $attr['data']['gradeOptions'],
							'value' => $obj->education_grade_id
						));
						?>
					</td>
					<td>
						<?php
						echo $this->Form->input("institution_site_section_students.$i.student_category_id", array(
							'label' => false,
							'options' => $attr['data']['categoryOptions'],
							'value' => $obj->student_category_id
						));
						?>
					</td>
					<td> 
						<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this)">
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
					<td><?php echo (is_object($obj->education_grade) ? $obj->education_grade->name : ''); ?></td>
					<td><?php echo $attr['data']['categoryOptions'][$obj->student_category_id] ?></td>
				</tr>

			<?php endif;?>

		<?php endforeach ?>
				
			</tbody>
		</table>
	</div>	
