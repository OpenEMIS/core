<?php
	$alias = isset($attr['alias']) ? $attr['alias'] : [];
	$fieldKey = isset($attr['fieldKey']) ? $attr['fieldKey'] : 0;
	$sectionOptions = isset($attr['sectionOptions']) ? $attr['sectionOptions'] : [];
	$tableHeaders = isset($attr['tableHeaders']) ? $attr['tableHeaders'] : [];
	$tableCells = isset($attr['tableCells']) ? $attr['tableCells'] : [];
?>

<?php if ($action == 'view') : ?>
	<?php
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
		$url['field_id'] = $fieldKey;
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
	<div class="table-in-view">
		<table class="table">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php else : ?>
	<div class="clearfix"></div>
	<hr>
	<h3><?= $attr['attr']['label']; ?></h3>
	<div class="clearfix">
		<?php
			echo $this->Form->input($alias.".institution_section", [
				'label' => $this->Label->get('InstitutionSurveys.section'),
				'type' => 'select',
				'options' => $sectionOptions,
				'onchange' => "$('#reload').val('changeSection').click();"
			]);
		?>
	</div>
	<div class="table-responsive">
		<table class="table table-curved">
			<thead><?= $this->Html->tableHeaders($tableHeaders) ?></thead>
			<tbody><?= $this->Html->tableCells($tableCells) ?></tbody>
		</table>
	</div>
<?php endif ?>
