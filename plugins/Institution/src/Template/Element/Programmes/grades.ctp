<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add') : ?>

<div class="input clearfix">
	<label class="pull-left"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
	<div class="table-in-view col-md-4 table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
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
						$checkboxOptions = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false, 'div' => false];
						$checkboxOptions['value'] = $obj->id;
						if (in_array($obj->id, $attr['exists'])) {
							$checkboxOptions['disabled'] = 'disabled';
							$checkboxOptions['checked'] = 'checked';
						}
						echo $this->Form->input("grades.$i.education_grade_id", $checkboxOptions);
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

<?php endif ?>
