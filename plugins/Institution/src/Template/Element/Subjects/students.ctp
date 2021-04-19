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
		$this->Form->unlockField('InstitutionSubjects.subject_students');
		?>
	</div>
	<?php endif;?>

	<div class="table-wrapper">
		<div class="table-responsive">
			<table class="table table-curved table-checkable table-input">
				<thead>
					<tr>
						<th><?= $this->Label->get('General.openemis_no'); ?></th>
						<th><?= $this->Label->get('Users.name'); ?></th>
						<th><?= __('Class') ?></th>
						<th><?= $this->Label->get('Users.gender_id'); ?></th>
						<th><?= __('Student Status') ?></th>
						<?php

						if ($action!='view') {
							echo '<th class="cell-delete"></th>';
						}
						?>
					</tr>
				</thead>

				<tbody>
				<?php
				$configureStudentName = $attr['data']['configure_student_name'];
				foreach($attr['data']['students'] as $i => $obj) :
					if ($action=='edit') :
						$n = $obj->student_id;
						if (is_object($obj->user)) {
							$userData = [
								'openemis_no' => $obj->user->openemis_no,
								'name' => $obj->user->name,
								'gender' => ['name' => $obj->user->gender->name],
								'student_status' => ['name' => $obj->student_status->name]
							];
						} else if (is_array($obj->user)) {
							$userData = $obj->user;
							$userData['student_status']['name'] = $obj['student_status']['name'];
						} else {
							/**
							 * @todo
							 */
							$userData = false;
						}
				?>
					<tr>
						<?php

						//send encoded hidden fields to avoid post limit
						$hiddenField = '';
						$hiddenField = $this->ControllerAction->paramsEncode([
							'id' => $obj->id, 
							'student_id' => $n,
							'student_status_id' => $obj->student_status_id, 
							'student_status' => [
								'name' => $userData['student_status']['name']
							],
							'institution_subject_id' => $obj->institution_subject_id, 
							'institution_class_id' => $obj->institution_class_id, 
							'institution_id' => $obj->institution_id,
							'academic_period_id' => $obj->academic_period_id, 
							'education_subject_id' => $obj->education_subject_id,
							'education_grade_id' => $obj->education_grade_id, 
							'user' =>[
								'id' => $n,
								'openemis_no' => $userData['openemis_no'],
								'name' => $userData['name'], 
								'gender' => [
									'name' => $userData['gender']['name']
								]
							]
						]);

						echo $this->Form->hidden("InstitutionSubjects.subject_students.$n.hiddenField", [ 'value' => $hiddenField ]);

						?>
						<td><?= $userData['openemis_no'] ?></td>
						<td><?= $userData['name'] ?></td>
						<td><?= __($userData['gender']['name']) ?></td>
						<td><?= __($userData['student_status']['name']) ?></td>
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
						<td>
							<?php
								$url = [
									'plugin' => 'Institution',
									'controller' => 'Institutions',
									'action' => 'StudentUser',
									'view',
									$this->ControllerAction->paramsEncode(['id' => $obj->student_user_id])
								];

								$newUrl = $this->ControllerAction->setQueryString($url, ['institution_id' => $obj->institution_id]);
							?>
							<?= $this->html->link($obj->student_openemis_no, $newUrl) ?>
						</td>
						<td><?php 
							if (!empty($obj->user->identity_number)) {
								if ($configureStudentName) {
									echo $obj->user->identity_number.' - '.$obj->user->name;
								} else {
									echo $obj->user->identity_number.' - '.$obj->user->first_name.' '.$obj->user->last_name;
									}
							}
							else {
							echo $obj->user->name;
							}
							?></td>
						<td><?= __($obj->institution_class->name) ?></td>
						<td><?= $obj->student_gender ?></td>
						<td><?= __($obj->class_student->student_status->name) ?></td>
					</tr>

				<?php endif;?>

			<?php endforeach; ?>

				</tbody>
			</table>
		</div>
	</div>
