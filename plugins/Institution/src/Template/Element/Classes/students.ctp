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
	
	<div class="table-wrapper">
		<div class="table-responsive">
			<table class="table table-curved">
				<thead>
					<tr>
						<th><?= $this->Label->get('Users.openemis_no'); ?></th>
						<th><?= $this->Label->get('Users.name'); ?></th>
						<th><?= $this->Label->get('Users.gender_id'); ?></th>
						<th><?= $this->Label->get($attr['model'] . '.education_grade'); ?></th>
						<th><?= __('Student Status') ?></th>
						<?php if ($action=='edit') { ?>
							<th class="cell-delete"></th>
						<?php } ?>
					</tr>
				</thead>

				<tbody>
				<?php 
				foreach($attr['data']['students'] as $i => $obj) : 

					if ($action=='edit') :
						$n = $obj->student_id;
				?>

					<tr>
						<?php
						echo $this->Form->hidden("InstitutionClasses.institution_class_students.$n.id", [ 'value'=> $obj->id ]);
						echo $this->Form->hidden("InstitutionClasses.institution_class_students.$n.student_id", [ 'value'=> $obj->student_id ]);
						echo $this->Form->hidden("InstitutionClasses.institution_class_students.$n.institution_class_id", [ 'value'=> $obj->institution_class_id ]);
						echo $this->Form->hidden("InstitutionClasses.institution_class_students.$n.education_grade_id", [ 'value'=> $obj->education_grade_id ]);
						echo $this->Form->hidden("InstitutionClasses.institution_class_students.$n.student_status_id", [ 'value'=> $obj->student_status_id ]);
						?>
						<td><?= $obj->user->openemis_no ?></td>
						<td><?= $obj->user->name ?></td>
						<td><?= __($obj->user->gender->name) ?></td>
						<td><?= $obj->education_grade->name ?></td>
						<td><?= __($obj->student_status->name) ?></td>
						<td> 
							<!--<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemoveAndReload(this)">-->
							<button class="btn btn-dropdown action-toggle btn-single-action" type="button" aria-expanded="true" onclick="jsTable.doRemove(this);">
								<?= __('<i class="fa fa-close"></i> Remove') ?>
							</button>
						</td>
					</tr>

				<?php else:?>

					<tr>
						<td>
							<?= $this->html->link($obj->user->openemis_no, [
									'plugin' => 'Institution',
									'controller' => 'Institutions',
									'action' => 'StudentUser',
									'view',
									$obj->user->id
								]) ?>
						</td>
						<td><?= $obj->user->name ?></td>
						<td><?= $obj->user->gender->name ?></td>
						<td><?= (is_object($obj->education_grade) ? $obj->education_grade->name : ''); ?></td>
						<td><?= __($obj->student_status->name) ?></td>
					</tr>

				<?php endif;?>

			<?php endforeach ?>
					
				</tbody>
			</table>
		</div>	
	</div>
