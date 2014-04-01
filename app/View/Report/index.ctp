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
	
	<fieldset class="section_group">
		<legend><?php echo __('System Reports'); ?></legend>
		<table class="table">
			<thead>
				<tr>
					<td>Name</td>
					<td>Description</td>
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
					<td>Name</td>
					<td>Description</td>
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
	<?php
		/*
		echo '<div id="repoManLeftCol">';
		echo $this->Form->create('ReportManager');
		echo '<fieldset>';
		echo '<legend>' . __d('report_manager','New report',true) . '</legend>';        
		echo $this->Form->input('model',array(
			'type'=>'select',            
			'label'=>__d('report_manager','Model',true),
			'options'=>$models,
			'empty'=>__d('report_manager','--Select--',true)
			));
		
		echo '<div id="ReportManagerOneToManyOptionSelect">';
		echo $this->Form->input('one_to_many_option',array(
			'type'=>'select',
			'label'=>__d('report_manager','One to many option',true),
			'options'=>array(),
			'empty'=>__d('report_manager','<None>',true)
			));
		echo '</div>';
		echo $this->Form->input('new',array(
			'type'=>'hidden',
			'value'=>'1'
			));        
		echo '</fieldset>';
		echo $this->Form->submit(__d('report_manager','New',true),array('name'=>'new'));
		echo $this->Form->end();
		echo '</div>';
		
		echo '<div id="repoManMiddleCol">';
		
		echo $this->Html->tag('h2',__d('report_manager','OR',true));
		
		echo '</div>';
		
		echo '<div id="repoManRightCol">';
		echo $this->Form->create('ReportManager');
		echo '<fieldset>';
		echo '<legend>' . __d('report_manager','Load report',true) . '</legend>';        
		
		echo '<div id="ReportManagerSavedReportOptionContainer">';
		echo $this->Form->input('saved_report_option',array(
			'type'=>'select',
			'label'=>__d('report_manager','Saved reports',true),
			'options'=>$files,
			'empty'=>__d('report_manager','--Select--',true)
			));
		echo '</div>';
		echo $this->Form->input('load',array(
			'type'=>'hidden',
			'value'=>'1'
			));        
		echo '<button type="button" class="deleteReport">' . __d('report_manager','Delete',true) . '</button>';
		echo '</fieldset>';
		echo $this->Form->submit(__d('report_manager','Load',true),array('name'=>'load'));
		echo $this->Form->end();
		echo '</div>';
		*/
	?>
</div>