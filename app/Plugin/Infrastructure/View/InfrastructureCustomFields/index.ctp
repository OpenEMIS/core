<?php
echo $this->Html->css('/Infrastructure/css/infrastructure', 'stylesheet', array('inline' => false));
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
	}
$this->end();

$this->start('contentBody');
	echo $this->element('nav_tabs');
	echo $this->element('/custom_fields/index');
$this->end();
?>
