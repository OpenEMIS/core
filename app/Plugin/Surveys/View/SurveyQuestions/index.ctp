<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	if(isset($selectedParent)) {
		if ($_add) {
		    echo $this->Html->link(__('Add'), array('action' => 'add', 'module' => $selectedModule, 'parent' => $selectedParent), array('class' => 'divider'));
		}
		if ($_edit) {
		    echo $this->Html->link(__('Reorder'), array('action' => 'reorder', 'module' => $selectedModule, 'parent' => $selectedParent), array('class' => 'divider'));
		}
		echo $this->Html->link(__('Preview'), array('action' => 'preview', 'module' => $selectedModule, 'parent' => $selectedParent), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('/custom_fields/index');
$this->end();
?>
