<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentId', 'institution-list');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if($_add) {
	echo $this->Html->link($this->Label->get('general.add'), array('action' => 'contactsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

foreach($contactOptions as $key=>$ct){
    echo '<fieldset class="section_group"><legend>'. __($ct).'</legend>';
    
    
    $tableHeaders = array(__('Description'), __('Value'), __('Preferred'));
    $tableData = array();
    foreach($data as $obj) {
        if($obj['ContactType']['contact_option_id']==$key){
            $symbol = $this->Utility->checkOrCrossMarker($obj['StudentContact']['preferred']==1);
            $row = array();
            $row[] = $obj['ContactType']['name'] ;
            $row[] = $this->Html->link($obj['StudentContact']['value'], array('action' => 'contactsView', $obj['StudentContact']['id']), array('escape' => false));
            $row[] = array($symbol, array('class' => 'center')) ;
            $tableData[] = $row;
        }
    }
    echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
    echo '</fieldset>';
}
$this->end(); 
?>
