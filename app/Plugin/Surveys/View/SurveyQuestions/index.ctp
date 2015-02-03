<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	if(isset($selectedGroup)) {
		if ($_add) {
		    echo $this->Html->link(__('Add'), array('action' => 'add', 'module' => $selectedModule, 'group' => $selectedGroup), array('class' => 'divider'));
		}
		if ($_edit) {
		    echo $this->Html->link(__('Reorder'), array('action' => 'reorder', 'module' => $selectedModule, 'group' => $selectedGroup), array('class' => 'divider'));
		}
		echo $this->Html->link(__('Preview'), array('action' => 'preview', 'module' => $selectedModule, 'group' => $selectedGroup), array('class' => 'divider'));
		echo $this->Html->link(__('Download'), array('action' => 'download', 'xform', $selectedGroup, 0), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
	echo $this->element('/custom_fields/index');
$this->end();
?>
