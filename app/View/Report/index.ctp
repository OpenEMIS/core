<?php
echo $this->Html->script('report/index', false);
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Custom Reports'));
$this->start('contentActions');
if($_accessControl->check($this->params['controller'], 'reportsNew')) {
	echo $this->Html->link(__('New'), array('action' => 'reportsNew'), array('class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'report');

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<script type="text/javascript">
    firstLevel = "<?php echo Router::url('/'); ?>";
</script>

<fieldset class="section_group">
	<legend><?php echo __('Shared Reports'); ?></legend>
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<td><?php echo __('Name'); ?></td>
				<td><?php echo __('Description'); ?></td>
			</tr>
		</thead>
		<tbody>
			<?php foreach($globalReport as $obj) : ?>
			<tr>
				<td><?php echo $this->Html->link($obj['ReportTemplate']['name'], array('action' => 'reportsView', $obj['ReportTemplate']['id'])); ?></td>
				<td><?php echo $obj['ReportTemplate']['description']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</fieldset>

<fieldset class="section_group">
	<legend><?php echo __('My Reports'); ?></legend>
	<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<td><?php echo __('Name'); ?></td>
				<td><?php echo __('Description'); ?></td>
			</tr>
		</thead>
		<tbody>
			<?php foreach($myReport as $obj) : ?>
			<tr>
				<td><?php echo $this->Html->link($obj['ReportTemplate']['name'], array('action' => 'reportsView', $obj['ReportTemplate']['id'])); ?></td>
				<td><?php echo $obj['ReportTemplate']['description']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
</fieldset>

<?php $this->end(); ?>