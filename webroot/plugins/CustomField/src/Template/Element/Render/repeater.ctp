<?php
	$fieldPrefix = $ControllerAction['table']->alias() . '.institution_repeater_surveys.' . $attr['customField']->id;
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>
<?php if ($ControllerAction['action'] == 'view') : ?>
	<?php
		$fieldId = isset($attr['customField']->id) ? $attr['customField']->id : 0;

		$url = [
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action']
		];
		if (!empty($this->request->pass)) {
			$url = array_merge($url, $this->request->pass);
		}

		$dataNamedGroup = [];
		if (!empty($this->request->query)) {
			foreach ($this->request->query as $key => $value) {
				if (in_array($key, ['field_id', 'class_id'])) continue;
				echo $this->Form->hidden($key, [
					'value' => $value,
					'data-named-key' => $key
				]);
				$dataNamedGroup[] = $key;
			}
		}

		// Survey Question Id
		$url['field_id'] = $fieldId;
		// End
	?>

	<div class="clearfix"></div>
	<div class="table-wrapper">
		<div class="table-in-view">
			<table class="table">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php elseif ($ControllerAction['action'] == 'add' || $ControllerAction['action'] == 'edit') : ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= $attr['attr']['label']; ?></h3>
	<div class="clearfix">
		<div class="input">
			<label><?= __('Add More'); ?></label>
			<div class="input-form-wrapper">
				<div class="table-toolbar">
					<button class="btn btn-default btn-xs" onclick="$('.repeater-question-id').val(<?= $attr['customField']->id; ?>);$('#reload').val('addRepeater').click();return false;">
						<i class="fa fa-plus"></i>
						<span><?= __('Add'); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>
	<div class="table-wrapper">
		<div class="table-responsive">
			<table class="table table-curved">
				<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
				<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
			</table>
		</div>
	</div>
<?php endif ?>
