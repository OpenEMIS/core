<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentBody');
if (count($data) > 0) {
	foreach ($data as $module => $arrVals) {
		$tableHeaders = array(__('Name'), __('Types'));
		$tableData = array();
		foreach ($arrVals as $arrTypVals) {
			$row = array();
			$row[] =  __($arrTypVals['name']); 
			$tempTypes = '';
			foreach ($arrTypVals['types'] as $val) {
				$tempTypes = $this->Html->link(__($val), array('action' => 'reportGen', $arrTypVals['name'], $val));
			}
			
			$row[] = array($tempTypes, array('class'=>'center')) ;
			$tableData[] = $row;
		}
		echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
	
	}
}
$this->end();
?>