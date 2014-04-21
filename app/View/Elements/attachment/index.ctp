<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('attachments', 'stylesheet', array('inline' => false));
echo $this->Html->script('attachments', false);

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'attachmentsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('File'), __('Description'), __('File Type'), __('Uploaded On'));
$tableData = array();
foreach($data as $value) {
	$obj = $value[$_model];
	$fileext = strtolower(pathinfo($obj['file_name'], PATHINFO_EXTENSION));
	$ext = array_key_exists($fileext, $arrFileExtensions) ? $arrFileExtensions[$fileext] : $fileext;
	$link = $this->Html->link($obj['name'], array('action' => 'attachmentsView', $obj['id']));
	$row = array();
	$row[] = $link;
	$row[] = $obj['description'];
	$row[] = __($ext);
	$row[] = $obj['created'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>
