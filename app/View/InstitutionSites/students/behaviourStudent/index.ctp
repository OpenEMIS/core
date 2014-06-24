<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution_site', 'stylesheet', array('inline' => false));
echo $this->Html->script('institution_site_classes', false);

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'studentBehaviour');
$this->assign('contentHeader', __('List of Behaviour'));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('controller' => 'InstitutionSites', 'action' => 'behaviourStudentList'/*, $id*/), array('class' => 'divider'));
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'behaviourStudentAdd', $id), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
echo $this->Form->create('InstitutionSite', array(
	'url' => array('controller' => 'InstitutionSites', 'action' => 'behaviourStudent'),
	'inputDefaults' => array('label' => false, 'div' => false)
));

$tableHeaders = array($this->Label->get('general.date'), $this->Label->get('general.category'), $this->Label->get('general.title'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
        $row[] = array($obj['StudentBehaviour']['date_of_behaviour'], array('class'=>array('center'))) ;
        $row[] = array($obj['StudentBehaviourCategory']['name'], array('class'=>array('center'))) ;
		$row[] = $this->Html->link($obj['StudentBehaviour']['title'], array('action' => 'behaviourStudentView', $obj['StudentBehaviour']['id'])); ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); ?>
