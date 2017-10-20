<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if (in_array($action, ['associated'])) : ?>
	<div class="input clearfix">
		<label><?= isset($attr['label']) ? __($attr['label']) : __($attr['field']) ?></label>
		<div class="table-wrapper">
			<div class="table-in-view">
				<table class="table table-checkable">
					<thead>
						<tr>
							<th><?= __('Feature') ?></th>
							<th><?= __('No of Records') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($attr['data'] as $i => $obj) : ?>
							<tr>
								<td><?= $i ?></td>
								<td><?= $obj ?></td>
							</tr>
						<?php endforeach ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
<?php endif ?>
