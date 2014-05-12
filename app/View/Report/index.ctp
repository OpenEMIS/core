<?php
echo $this->Html->script('report/index', false);
//echo $this->Html->css('report/report_manager', 'stylesheet', array('inline' => false));
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
?>

<script type="text/javascript">
    firstLevel = "<?php echo Router::url('/'); ?>";
</script>

<?php echo $this->element('breadcrumb'); ?>

<div id="report" class="content_wrapper">
	<h1>
		<span><?php echo __('Custom Reports'); ?></span>
		<?php
		if($_accessControl->check($this->params['controller'], 'reportsNew')) {
			echo $this->Html->link(__('New'), array('action' => 'reportsNew'), array('class' => 'divider'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	<fieldset class="section_group">
		<legend><?php echo __('Shared Reports'); ?></legend>
		<table class="table">
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
	</fieldset>
	
	<fieldset class="section_group">
		<legend><?php echo __('My Reports'); ?></legend>
		<table class="table" >
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
	</fieldset>
</div>