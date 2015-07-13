<?php
if (isset($attr['null']) && empty($attr['null'])) {
	$required = 'required';
} else {
	$required = '';
}
list($prefix, $enable) = explode(',', $data->value);
if ($enable) {
	$enable = 'checked';
}
?>
<div class="input clearfix <?= $required ?>">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= __('Value') ?></label>
	
	<div class="table-in-view">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<th class="checkbox-column">Enable</th>
					<th><?= __('Prefix Value') ?></th>
				</tr>
			</thead>

			<tbody>
				<tr>
					
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?= $attr['model'].'[value][enable]' ?>" value="1" <?= $enable ?> />
					</td>
					
					<td>
						<input type="text" name="<?= $attr['model'].'[value][prefix]' ?>" value="<?= $prefix ?>" />
					</td>

				</tr>
			</tbody>

		</table>

	</div>
</div>
