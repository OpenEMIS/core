<?php /*

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="identity" class="content_wrapper">
	<h1>
		<span><?php echo __('Identities'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'identitiesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/identitiesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Type'); ?></div>
			<div class="table_cell"><?php echo __('Number'); ?></div>
			<div class="table_cell"><?php echo __('Issued'); ?></div>
			<div class="table_cell"><?php echo __('Expiry'); ?></div>
			<div class="table_cell"><?php echo __('Location'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StaffIdentity']['id']; ?>">
				<div class="table_cell"><?php echo $obj['IdentityType']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffIdentity']['number']; ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffIdentity']['issue_date']); ?></div>
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffIdentity']['expiry_date']); ?></div>
				<div class="table_cell"><?php echo $obj['StaffIdentity']['issue_location']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
 * 
 */?>

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'identitiesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Type'), __('Number'), __('Issued'), __('Expiry'), __('Location'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $obj['IdentityType']['name'] ;
        $row[] = $this->Html->link($obj[$model]['number'], array('action' => 'identitiesView', $obj[$model]['id']), array('escape' => false)) ;
	$row[] = $obj[$model]['issue_date'];
	$row[] = $obj[$model]['expiry_date'];
        $row[] = $obj[$model]['issue_location'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

 $this->end(); ?>
