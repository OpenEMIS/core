<?php /*

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="contact" class="content_wrapper">
	<h1>
		<span><?php echo __('Contacts'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'contactsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>
	<?php foreach($contactOptions as $key=>$ct){  ?>
	<fieldset class="section_group">
	<legend><?php echo __($ct);?></legend>
	<div class="table allow_hover full_width" action="Staff/contactsView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Description'); ?></div>
			<div class="table_cell"><?php echo __('Value'); ?></div>
			<div class="table_cell"><?php echo __('Preferred'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<?php if($obj['ContactType']['contact_option_id']==$key){?>
			<div class="table_row" row-id="<?php echo $obj['StaffContact']['id']; ?>">
				<div class="table_cell"><?php echo $obj['ContactType']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffContact']['value']; ?></div>
				<div class="table_cell cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['StaffContact']['preferred']==1); ?></div>
			</div>
			<?php } ?>
			<?php endforeach; ?>
		</div>
	</div>
	</fieldset>
	<?php } ?>
</div>
 * 
 */?>

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
            $symbol = $this->Utility->checkOrCrossMarker($obj[$model]['preferred']==1);
            $row = array();
            $row[] = $obj['ContactType']['name'] ;
            $row[] = $this->Html->link($obj[$model]['value'], array('action' => 'contactsView', $obj[$model]['id']), array('escape' => false));
            $row[] = array($symbol, array('class' => 'center')) ;
            $tableData[] = $row;
        }
    }
    echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
    echo '</fieldset>';
}
$this->end(); 
?>
