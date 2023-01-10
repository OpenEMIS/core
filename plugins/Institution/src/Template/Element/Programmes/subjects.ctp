<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="no-selection-label" kd-checkbox-radio/></th>
						<th><?= __('Code') ?></th>
						<th><?= __('Name') ?></th>
					</tr>
				</thead>

				<?php if (isset($attr['data'])) : ?>

				<tbody>
					<?php foreach ($attr['data'] as $i => $obj) : ?>
					<tr>
						<td class="checkbox-column">
							<?php
							$checkboxOptions = ['class' => 'no-selection-label', 'kd-checkbox-radio' => ''];
							$checkboxOptions['value'] = $obj->id;
								if (!empty($attr['exists']) && in_array($obj->id, $attr['exists'])) {
									$checkboxOptions['disabled'] = 'disabled';
									$checkboxOptions['checked'] = 'checked';
								}
							echo $this->Form->checkbox("grades.education_grade_subject_id.$i", $checkboxOptions);

							?>
						</td>
						<td><?= $obj->code ?></td>
						<td><?= $obj->name ?></td>
					</tr>
					<?php endforeach ?>
				</tbody>

				<?php endif ?>

			</table>
		</div>
	</div>
</div>
<?php elseif ($action == 'view') : ?>

<div class="input clearfix">
	
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable">
				<thead>
					<tr>
                                            <th><?= __('Code') ?></th>
						<th><?= __('Name') ?></th>
					</tr>
				</thead>

				<?php if (isset($attr['data'])) : ?>

				<tbody>
					<?php foreach ($attr['data'] as $i => $obj) : ?>
					<tr>						
						<td><?= $obj->code ?></td>
						<td><?= $obj->name ?></td>
					</tr>
					<?php endforeach ?>
				</tbody>

				<?php endif ?>

			</table>
		</div>
	</div>
</div>
<?php endif ?>
