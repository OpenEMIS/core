<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('table_cell', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back') , array('action' => 'rubricsTemplates'), array('class' => 'divider')); 
if ($_add) {
    echo $this->Html->link($this->Label->get('Quality.add_section_header'), array('action' => 'rubricsTemplatesHeaderAdd', $id), array('class' => 'divider'));
}

if ($_edit && !empty($data)) {
    echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'rubricsTemplatesHeaderReorder', $id), array('class' => 'divider'));
}

$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Section Header'), __('Action'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $this->Html->link('<div>' . $obj[$modelName]['title'] . '</div>', array('action' => 'rubricsTemplatesSubheaderView', $obj[$modelName]['id']), array('escape' => false));
	$row[] = array($this->Html->link('<div>' . $this->Label->get('general.view_details') . '</div>', array('action' => 'rubricsTemplatesHeaderView',$id, $obj[$modelName]['id']), array('escape' => false)), array('class'=>'cell-action'));
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>  