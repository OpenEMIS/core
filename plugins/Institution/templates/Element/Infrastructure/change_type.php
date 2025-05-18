<?php
	$alias = $ControllerAction['table']->getAlias();

	$url = [
		'plugin' => $this->request->getParam('plugin'),
	    'controller' => $this->request->getParam('controller'),
	    'action' => $this->request->getParam('action'),
	    'institutionId' => $this->request->getParam('institutionId')
	];
	
	if (!empty($this->request->getParam('pass'))) {
		$url = array_merge($url, $this->request->getParam('pass'));
	}

	$dataNamedGroup = [];
	if (!empty($this->request->getQuery())) {
		foreach ($this->request->getQuery() as $key => $value) {
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
