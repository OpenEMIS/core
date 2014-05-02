<?php /*

<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="salary" class="content_wrapper">
	<h1>
		<span><?php echo __('Salary'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'salariesAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/salariesView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Gross'); ?></div>
			<div class="table_cell"><?php echo __('Additions'); ?></div>
			<div class="table_cell"><?php echo __('Deductions'); ?></div>
			<div class="table_cell"><?php echo __('Net'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StaffSalary']['id']; ?>">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffSalary']['salary_date']); ?></div>
				<div class="table_cell"><?php echo $obj['StaffSalary']['gross_salary']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffSalary']['additions']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffSalary']['deductions']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffSalary']['net_salary']; ?></div>
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
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'salariesAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Gross'), __('Additions'), __('Deductions'), __('Net'));
$tableData = array();

foreach($data as $obj) {
	$row = array();
        $row[] = $this->Html->link($obj[$model]['salary_date'], array('action' => 'salariesView', $obj[$model]['id']), array('escape' => false));
        $row[] = $obj[$model]['gross_salary'] ;
        $row[] = $obj[$model]['additions'] ;
        $row[] = $obj[$model]['deductions'] ;
		$row[] = $obj[$model]['net_salary'] ;
	$tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end(); 

?>