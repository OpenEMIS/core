<?php
	$alias = $ControllerAction['table']->alias();

	$url = [
		'plugin' => $this->request->param('plugin'),
	    'controller' => $this->request->param('controller'),
	    'action' => $this->request->param('action'),
	    'institutionId' => $this->request->param('institutionId')
	];
	if (!empty($this->request->pass)) {
		$url = array_merge($url, $this->request->pass);
	}

	$dataNamedGroup = [];
	if (!empty($this->request->query)) {
		foreach ($this->request->query as $key => $value) {
			if (in_array($key, ['edit_type'])) continue;
			echo $this->Form->hidden("$alias.$key", [
				'value' => $value,
				'data-named-key' => $key
			]);
			$dataNamedGroup[] = $key;
		}
	}

	$baseUrl = $this->Url->build($url);

	$inputOptions = [
		'class' => 'form-control',
		'label' => isset($attr['label']) ? $attr['label'] : $attr['field'],
		'options' => $editTypeOptions,
		'url' => $baseUrl,
		'data-named-key' => 'edit_type',
		'escape' => false
	];
	if (!empty($dataNamedGroup)) {
		$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
	}
	echo $this->Form->input($alias.".change_type", $inputOptions);
?>
