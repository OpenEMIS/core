<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'staffBehaviour');
$this->assign('contentHeader', __('List of Behaviour'));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('controller' => 'InstitutionSites', 'action' => 'behaviourStaffList'), array('class' => 'divider'));
if ($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'behaviourStaffAdd', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->Form->create('InstitutionSite', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'staffsBehaviour'),
	'inputDefaults' => array('label' => false, 'div' => false)
));

$tableHeaders = array($this->Label->get('general.date'), $this->Label->get('general.category'), $this->Label->get('general.title'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
        $row[] = array($obj['StaffBehaviour']['date_of_behaviour'], array('class'=>array('center'))) ;
        $row[] = array($obj['StaffBehaviourCategory']['name'], array('class'=>array('center'))) ;
		$row[] = $this->Html->link($obj['StaffBehaviour']['title'], array('action' => 'behaviourStaffView', $obj['StaffBehaviour']['id'])); ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); ?>