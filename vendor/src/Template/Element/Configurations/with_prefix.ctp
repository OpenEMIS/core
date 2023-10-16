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
	<label for="<?= $attr['id'] ?>"><?= __('Value') ?></label>

	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table table-checkable table-input">
				<thead>
					<tr>
						<th class="checkbox-column">Enable</th>
						<th><?= __('Prefix Value') ?></th>
					</tr>
				</thead>

				<tbody>
					<tr>

						<td class="checkbox-column">
							<?= $this->Form->checkbox($attr['model'].'.value.enable', [
									'value' => 1,
									'checked' => $enable,
									'class' => 'no-selection-label',
									'kd-checkbox-radio' => ''
								]);
							?>
						</td>

						<td>
							<?=	$this->Form->input($attr['model'].'.value.prefix', [
									'value' => $prefix,
									'type' => 'text',
									'label' => false
								]);
							?>
						</td>

					</tr>
				</tbody>
			</table>
		</div>
	</div>

</div>
