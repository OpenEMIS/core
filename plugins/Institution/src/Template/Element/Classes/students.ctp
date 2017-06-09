	<div class="clearfix"></div>

	<hr>

	<h3><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></h3>
	
	<div class="dropdown-filter">
		<div class="filter-label">
			<i class="fa fa-filter"></i>
			<label>Filter</label>
		</div>

		<?php 
			$gradeOptions = $attr['data']['filter']['education_grades']['options'];
			$selectedGrade = $attr['data']['filter']['education_grades']['selected'];
		?>
			<div class="select">
				<label><?=__('Education Grade');?>:</label>
				<div class="input-select-wrapper">
					<select>
						<?php foreach ($gradeOptions as $key => $value) { ?>
							<option 
								value="<?=$key;?>" 
								onClick="window.location.href='<?= $this->Url->build($value['url']); ?>'"
								<?php if ($selectedGrade == $key) { ?>
									selected
								<?php } ?>
							><?=__($value['name']);?></option>
						<?php } ?>
					</select>
				</div>
			</div>

		<?php 
			$statusOptions = $attr['data']['filter']['student_status']['options'];
			$selectedStatus = $attr['data']['filter']['student_status']['selected'];
		?>
			<div class="select">
				<label><?=__('Student Status');?>:</label>
				<div class="input-select-wrapper">
					<select>
						<?php foreach ($statusOptions as $key => $value) { ?>
							<option 
								value="<?=$key;?>" 
								onClick="window.location.href='<?= $this->Url->build($value['url']); ?>'"
								<?php if ($selectedStatus == $key) { ?>
									selected
								<?php } ?>
							><?=__($value['name']);?></option>
						<?php } ?>
					</select>
				</div>
			</div>

		<?php 
			$genderOptions = $attr['data']['filter']['genders']['options'];
			$selectedGender = $attr['data']['filter']['genders']['selected'];
		?>
			<div class="select">
				<label><?=__('Gender');?>:</label>
				<div class="input-select-wrapper">
					<select>
						<?php foreach ($genderOptions as $key => $value) { ?>
							<option 
								value="<?=$key;?>" 
								onClick="window.location.href='<?= $this->Url->build($value['url']); ?>'"
								<?php if ($selectedGender == $key) { ?>
									selected
								<?php } ?>
							><?=__($value['name']);?></option>
						<?php } ?>
					</select>
				</div>
			</div>
	</div>

	<?php if ($action=='edit') :?>
	<div class="clearfix">
	<?php
		echo $this->Form->input('student_id', array(
			'options' => $attr['data']['studentOptions'],
			'label' => $this->Label->get('Users.add_student'),
			'onchange' => "$('#reload').val('add').click();"
		));
		$this->Form->unlockField('InstitutionClasses.class_students');
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
						echo $this->Form->hidden("InstitutionClasses.class_students.$n.id", [ 'value'=> $obj->id ]);
						echo $this->Form->hidden("InstitutionClasses.class_students.$n.student_id", [ 'value'=> $obj->student_id ]);
						echo $this->Form->hidden("InstitutionClasses.class_students.$n.institution_class_id", [ 'value'=> $obj->institution_class_id ]);
						echo $this->Form->hidden("InstitutionClasses.class_students.$n.education_grade_id", [ 'value'=> $obj->education_grade_id ]);
						echo $this->Form->hidden("InstitutionClasses.class_students.$n.student_status_id", [ 'value'=> $obj->student_status_id ]);
						echo $this->Form->hidden("InstitutionClasses.class_students.$n.academic_period_id", [ 'value'=> $obj->academic_period_id ]);
						echo $this->Form->hidden("InstitutionClasses.class_students.$n.institution_id", [ 'value'=> $obj->institution_id ]);
						?>
						<td><?= $obj->user->openemis_no ?></td>
						<td><?= $obj->user->name ?></td>
						<td><?= __($obj->user->gender->name) ?></td>
						<td><?= $obj->education_grade->name ?></td>
						<td><?= __($obj->student_status_name) ?></td>
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
						<td><?= $obj->student_name ?></td>
						<td><?= __($obj->student_gender) ?></td>
						<td><?= (is_object($obj->education_grade) ? $obj->education_grade->name : ''); ?></td>
						<td><?= __($obj->student_status_name) ?></td>
					</tr>

				<?php endif;?>

			<?php endforeach ?>

				</tbody>
			</table>
		</div>
	</div>
