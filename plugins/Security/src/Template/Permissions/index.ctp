<?php
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

	foreach ($data as $section => $list) {
		echo '<div class="section-header">' . $section . '</div>';
		echo '<div class="table-wrapper">
			<div class="table-responsive">
			<table class="table table-curved">
				<thead>' . $this->Html->tableHeaders($tableHeaders) . '</thead>
				<tbody>
			';

		foreach ($list as $obj) {
			echo '<tr>';
			echo '<td>' . $obj->name . '</td>';
			foreach ($operations as $op) {
				echo '<td class="center">' . $obj->Permissions[$op] . '</td>';
			}
			echo '</tr>';
		}

		echo '</tbody></table></div></div>';
	}
$this->end();
