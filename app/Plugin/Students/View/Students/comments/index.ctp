<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Comments'));
$this->start('contentActions');
if($_add) {
	echo $this->Html->link(__('Add'), array('action' => 'commentsAdd'), array('class' => 'divider'));
}
$this->end();

$this->start('contentBody');

?>
<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<?php echo $this->Html->tableHeaders(array(__('Date'), __('Title'), __('Comment'))); ?>
		</thead>
		<tbody>
			<?php
			$tableData = array();
			foreach($data as $obj) {
				$tableData[] = array(
					$this->Utility->formatDate($obj[$model]['comment_date'], null, false),
					$this->Html->link($obj[$model]['title'], array('action' => 'commentsView', $obj[$model]['id'])),
					$obj[$model]['comment']
				);
			}
			echo $this->Html->tableCells($tableData);
			?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
