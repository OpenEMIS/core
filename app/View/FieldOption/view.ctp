<?php
extract($data);
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array_merge(array('action' => 'index', $selectedOption));
echo $this->Html->link(__('Back'), $params, array('class' => 'divider'));
if($_edit) {
	$params = array_merge(array('action' => 'indexEdit'));//, $parameters);
	//echo $this->Html->link(__('Reorder'), $params, array('class' => 'divider'));
}
$this->end(); // end contentActions

$this->start('contentBody');
echo $this->element('layout/view', array('fields' => $fields, 'data' => $data));
//pr($fields);
?>


<?php $this->end(); ?>
