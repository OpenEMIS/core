<?php 
foreach ($contactOptions as $key=>$ct) {
	echo '<fieldset class="section_group"><legend>'. __($ct).'</legend>';
	
	$tableHeaders = array(__('Description'), __('Value'), __('Preferred'));
	$tableData = array();
	// pr($data);
	foreach($data['UserContact'] as $obj) {
		if($obj['ContactType']['contact_option_id']==$key){
			$symbol = $this->Utility->checkOrCrossMarker($obj['UserContact']['preferred']==1);
			$row = array();
			$row[] = $obj['ContactType']['name'] ; 
			$row[] = $this->Html->link($obj['UserContact']['value'], array('action' => 'UserContact', 'view', $obj['UserContact']['id']), array('escape' => false));
			$row[] = array($symbol, array('class' => 'center')) ;
			$tableData[] = $row;
		}
	}
	echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
	echo '</fieldset>';
}
?>