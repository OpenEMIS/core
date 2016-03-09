<?php
	$fieldPrefix = $ControllerAction['table']->alias() . '.institution_student_surveys.' . $attr['customField']->id;
	$sectionOptions = isset($attr['attr']['sectionOptions']) ? $attr['attr']['sectionOptions'] : [];
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
				if (in_array($key, ['field_id', 'section_id'])) continue;
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

		$baseUrl = $this->Url->build($url);
		$template = $this->ControllerAction->getFormTemplate();
		$this->Form->templates($template);

		$inputOptions = [
			'class' => 'form-control',
			'label' => false,
			'options' => $sectionOptions,
			'url' => $baseUrl,
			'data-named-key' => 'section_id',
			'escape' => false
		];
		if (!empty($dataNamedGroup)) {
			$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
			$dataNamedGroup[] = 'section_id';
		}
		echo $this->Form->input('institution_section', $inputOptions);
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
		<?php
			echo $this->Form->input($fieldPrefix.".institution_section", [
				'label' => $this->Label->get('InstitutionSurveys.section'),
				'type' => 'select',
				'options' => $sectionOptions,
				'onchange' => "$('#reload').val('changeSection').click();"
			]);
		?>
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
