<?php /*

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="languages" class="content_wrapper">
	<h1>
		<span><?php echo __('Languages'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'languagesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/languagesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Language'); ?></div>
			<div class="table_cell"><?php echo __('Listening'); ?></div>
			<div class="table_cell"><?php echo __('Speaking'); ?></div>
			<div class="table_cell"><?php echo __('Reading'); ?></div>
			<div class="table_cell"><?php echo __('Writing'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StaffLanguage']['id']; ?>">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffLanguage']['evaluation_date']); ?></div>
				<div class="table_cell"><?php echo $obj['Language']['name']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffLanguage']['listening']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffLanguage']['speaking']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffLanguage']['reading']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffLanguage']['writing']; ?></div>
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
    echo $this->Html->link(__('Add'), array('action' => 'languagesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Language'), __('Listening'), __('Speaking'), __('Reading'), __('Writing'));
$tableData = array();
foreach($data as $obj) {
	$row = array();
	$row[] = $obj[$model]['evaluation_date'] ;
        $row[] = $this->Html->link($obj['Language']['name'], array('action' => 'languagesView', $obj[$model]['id']), array('escape' => false)) ;
	$row[] = $obj[$model]['listening'];
	$row[] = $obj[$model]['speaking'];
        $row[] = $obj[$model]['reading'];
        $row[] = $obj[$model]['writing'];
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));

 $this->end(); 
 ?>
