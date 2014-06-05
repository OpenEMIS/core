<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subheader));
$this->start('contentActions');

echo $this->Html->link($this->Label->get('general.back'), array('action' => 'rubricsTemplatesSubheaderView', $rubricTemplateHeaderId), array('class' => 'divider'));
if ($_add && !$disableDelete) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'rubricsTemplatesCriteriaAdd', $id, $rubricTemplateHeaderId), array('class' => 'divider'));
}
if ($_edit && !empty($data)) {

    echo $this->Html->link($this->Label->get('general.reorder'), array('action' => 'rubricsTemplatesCriteriaReorder', $id, $rubricTemplateHeaderId), array('class' => 'divider'));

}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Option'));
$tableData = array();
foreach ($data as $obj) {
	$row = array();
	$row[] = $this->Html->link($obj[$model]['name'], array('action' => 'rubricsTemplatesCriteriaView', $id, $rubricTemplateHeaderId, $obj[$model]['id']), array('escape' => false));
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); ?>  