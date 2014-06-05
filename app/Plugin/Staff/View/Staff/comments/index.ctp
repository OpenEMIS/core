<?php /*
<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('institution', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="bank_accounts" class="content_wrapper">
	<h1>
		<span><?php echo __('Comments'); ?></span>
		<?php
		if($_add) {
			echo $this->Html->link(__('Add'), array('action' => 'commentsAdd'), array('class' => 'divider'));
		}
		?>
	</h1>
		
	<?php echo $this->element('alert'); ?>

	<div class="table allow_hover full_width" action="Staff/commentsView/">
		<div class="table_head">
			<div class="table_cell"><?php echo __('Date'); ?></div>
			<div class="table_cell"><?php echo __('Title'); ?></div>
			<div class="table_cell"><?php echo __('Comment'); ?></div>
		</div>
		
		<div class="table_body">
			<?php foreach($list as $obj): ?>
			<div class="table_row" row-id="<?php echo $obj['StaffComment']['id']; ?>">
				<div class="table_cell"><?php echo $this->Utility->formatDate($obj['StaffComment']['comment_date']); ?></div>
				<div class="table_cell"><?php echo $obj['StaffComment']['title']; ?></div>
				<div class="table_cell"><?php echo $obj['StaffComment']['comment']; ?></div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>*/ ?>

<?php

echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Comments'));
$this->start('contentActions');
if ($_add) {
    echo $this->Html->link($this->Label->get('general.add'), array('action' => 'commentsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');
$tableHeaders = array(__('Date'), __('Title'), __('Comment'));
$tableData = array();

foreach ($data as $obj) {
    $row = array();
    $row[] = $obj[$model]['comment_date'];
    $row[] = $this->Html->link($obj[$model]['title'], array('action' => 'commentsView', $obj[$model]['id']));
    $row[] = $obj[$model]['comment'];
    $tableData[] = $row;
}
echo $this->element('templates/table', compact('tableHeaders', 'tableData'));
$this->end();
?>