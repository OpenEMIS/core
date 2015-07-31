<?php
echo $this->Html->script('Security.permission', ['block' => true]);

$this->extend('OpenEmis./Layout/Panel');
$this->start('toolbar');
	foreach ($toolbarButtons as $key => $btn) {
		if (!array_key_exists('type', $btn) || $btn['type'] == 'button') {
			echo $this->Html->link($btn['label'], $btn['url'], $btn['attr']);
		} else if ($btn['type'] == 'element') {
			echo $this->element($btn['element'], $btn['data'], $btn['options']);
		}
	}
$this->end();

$this->start('panelBody');
	$tableHeaders = [
		[__('Function') => ['style' => 'width: 300px']],
		[__('View') => ['class' => 'center']],
		[__('Edit') => ['class' => 'center']],
		[__('Add') => ['class' => 'center']],
		[__('Delete') => ['class' => 'center']],
		[__('Execute') => ['class' => 'center']]
	];

	$alias = $ControllerAction['table']->alias();

	$formOptions = $this->ControllerAction->getFormOptions();
	$formOptions['id'] = 'permissions'; // used for javascript toggle checkboxes, refer to security.js
	$template = $this->ControllerAction->getFormTemplate();
	$template['inputContainer'] = '{{content}}';
	$this->Form->templates($template);
	
	echo $this->Form->create(null, $formOptions);

	foreach ($data as $section => $list) {
		// add the sections
		echo '<h6 class="section-header" checkbox-toggle-target="' . $section . '">';
		// echo '<input type="checkbox" class="icheck-input" onchange="" />' . $section . '</h6>';
		echo '<input type="checkbox" />' . $section . '</h6>';

		// add the table under each section
		echo '<div class="table-responsive" checkbox-toggle="' . $section . '">
			<table class="table table-striped table-hover table-bordered">
				<thead>' . $this->Html->tableHeaders($tableHeaders) . '</thead>
				<tbody>
			';

		foreach ($list as $i => $obj) {
			echo '<tr>'; // start row
			echo '<td>' . $obj->name . '</td>'; // function name

			// function operations
			foreach ($operations as $op) {
				// $checkboxOptions = ['type' => 'checkbox', 'class' => 'icheck-input', 'label' => false, 'div' => false];
				$checkboxOptions = ['type' => 'checkbox', 'label' => false, 'div' => false];
				echo '<td class="center">';
				$permission = $obj->Permissions[$op];
				if ($permission == -1) {
					$checkboxOptions[] = 'disabled';
				} else if ($permission == 1) {
					$checkboxOptions[] = 'checked';
				}
				echo $this->Form->input("$alias.$i.$op", $checkboxOptions);
				echo '</td>';
			}
			// end function operations

			echo '</tr>'; // end row
		}

		echo '</tbody></table></div>';
	}

	echo $this->ControllerAction->getFormButtons();
	echo $this->Form->end();
$this->end();
