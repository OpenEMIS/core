<div class="clearfix"></div>

<hr>

<h3><?= $this->Label->get($attr['model'] . '.' . $attr['field']) ?></h3>

<div class="dropdown-filter">
	<div class="filter-label">
		<i class="fa fa-filter"></i>
		<label><?= __('Filter') ?></label>
	</div>

	<?php
		$gradeOptions = $attr['data']['filter']['education_grades']['options'];
		$selectedGrade = $attr['data']['filter']['education_grades']['selected'];
	?>
	<div class="select">
		<label><?= __('Education Grade'); ?>:</label>
		<div class="input-select-wrapper">
			<select onchange="window.location.href = this.value">
				<?php foreach ($gradeOptions as $key => $value) { ?>
					<option
						value="<?= $this->Url->build($value['url']); ?>"
						<?php if ($selectedGrade == $key) { ?>
							selected
						<?php } ?>
					><?= __($value['name']); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>

	<?php
		$statusOptions = $attr['data']['filter']['student_status']['options'];
		$selectedStatus = $attr['data']['filter']['student_status']['selected'];
	?>
	<div class="select">
		<label><?= __('Student Status'); ?>:</label>
		<div class="input-select-wrapper">
			<select onchange="window.location.href = this.value">
				<?php foreach ($statusOptions as $key => $value) { ?>
					<option
						value="<?= $this->Url->build($value['url']); ?>"
						<?php if ($selectedStatus == $key) { ?>
							selected
						<?php } ?>
					><?= __($value['name']); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>

	<?php
		$genderOptions = $attr['data']['filter']['genders']['options'];
		$selectedGender = $attr['data']['filter']['genders']['selected'];
	?>
	<div class="select">
		<label><?= __('Gender'); ?>:</label>
		<div class="input-select-wrapper">
			<select onchange="window.location.href = this.value">
				<?php foreach ($genderOptions as $key => $value) { ?>
					<option
						value="<?= $this->Url->build($value['url']); ?>"
						<?php if ($selectedGender == $key) { ?>
							selected
						<?php } ?>
					><?= __($value['name']); ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
</div>

<div class="table-wrapper">
	<div class="table-responsive">
		<table class="table table-curved table-sortable">
			<thead>
				<tr>
					<th><?= __('OpenEMIS ID') ?></th>
					<th><?= __('Name') ?></th>
					<th><?= __('Gender') ?></th>
					<th><?= __('Education Grade') ?></th>
					<th><?= __('Student Status') ?></th>
					<th><?= __('Special Needs') ?></th>
				</tr>
			</thead>

			<tbody>
			<?php foreach ($attr['data']['students'] as $obj) : ?>
				<tr>
					<td>
						<?php
							$url = [
								'plugin' => 'Institution',
								'controller' => 'Institutions',
								'action' => 'StudentUser',
								'view',
								$this->ControllerAction->paramsEncode([
									'student_id' => $obj->user->id,
									'id' => $obj->user->id,
									'institution_id' => $obj->institution_id,
								])
							];
						?>
						<?= $this->html->link($obj->user->openemis_no, $url) ?>
					</td>
					<td><?= $obj->user->name ?></td>
					<td><?= !empty($obj->user->gender) ? __($obj->user->gender->name) : '' ?></td>
					<td><?= !empty($obj->education_grade) ? $obj->education_grade->name : '' ?></td>
					<td><?= !empty($obj->student_status) ? __($obj->student_status->name) : '' ?></td>
					<td>
						<?php if ($obj->user->has_special_needs) { ?>
							<i class="fa fa-check"></i>
						<?php } else { ?>
							<i class="fa fa-times"></i>
						<?php } ?>
					</td>
				</tr>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
</div>
