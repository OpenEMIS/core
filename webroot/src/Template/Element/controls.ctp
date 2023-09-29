<?php
	$url = [
		'plugin' => $this->request->params['plugin'],
	    'controller' => $this->request->params['controller'],
	    'action' => $this->request->params['action']
	];
	
	if (!empty($firstLevelOptions)) {

		echo $this->Form->input('survey_module', array(
			'class' => 'form-control',
			'label' => false,
			'options' => $firstLevelOptions,
			'default' => $selectedFirstLevelKey . '=' . $selectedFirstLevel,
			'url' => $this->Url->build($url),
			'onchange' => 'jsForm.change(this);'
		));
	}

	if (!empty($secondLevelOptions)) {
		// foreach ($data as $key => $value) {
			// pr($this->request->params['action']);
		// }
		
		// $url['action'] = $selectedSecondLevelKey.'s';

		echo $this->Form->input('survey_module', array(
			'class' => 'form-control',
			'label' => false,
			'options' => $secondLevelOptions,
			'default' => $selectedSecondLevelKey . '=' . $selectedSecondLevel,
			'url' => $this->Url->build($url),
			'onchange' => 'jsForm.change(this);'
		));
	}

	if (!empty($thirdLevelOptions)) {

		$url[$selectedThirdLevelKey] = $selectedThirdLevel;

		echo $this->Form->input('survey_status', array(
			'class' => 'form-control',
			'label' => false,
			'options' => $thirdLevelOptions,
			'default' => $selectedThirdLevelKey . '=' . $selectedThirdLevel,
			'url' => $this->Url->build($url),
			'onchange' => 'jsForm.change(this);'
		));
	}
?>
