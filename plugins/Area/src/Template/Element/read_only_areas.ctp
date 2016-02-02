<?php

if ($ControllerAction['action'] == 'edit'):
	$template = $this->ControllerAction->getFormTemplate();
	$this->Form->templates($template);
	$formClass = 'form-control';

	foreach ($attr['list'] as $obj) {
		echo $this->Form->input('read_only_area', [
			'class' => $formClass,
			'div' => true,
			'label' => $attr['label'] .' - '. $obj['level'],
			'type' => 'text',
			'disabled' => true,
			'value' => $obj['area_name'],
		]);
	}

	echo $this->Form->input($ControllerAction['table']->alias().'.'.$attr['key'], [
			'type' => 'hidden',
			'value' => $attr['id'],
		]);

elseif ($ControllerAction['action'] == 'view') :
	foreach ($attr['list'] as $obj) {
?>
	<div class="row">
		<div class="col-xs-6 col-md-3 form-label"><?=$obj['level'] ?></div>
		<div class="form-input"><?=$obj['area_name'] ?></div>
	</div>
<?php
	}
endif;
?>
