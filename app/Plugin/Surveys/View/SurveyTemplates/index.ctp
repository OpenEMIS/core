<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	if ($_add) {
	    echo $this->Html->link(__('Add'), array('action' => 'add', 'module' => $selectedModule), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<div class="row page-controls">
	<?php
		echo $this->Form->input('survey_module_id', array(
			'class' => 'form-control',
			'label' => false,
			'options' => $moduleOptions,
			'default' => 'module:' . $selectedModule,
			'div' => 'col-md-3',
			'url' => $this->params['controller'] . '/index',
			'onchange' => 'jsForm.change(this)'
		));
	?>
</div>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Description'); ?></th>
				<th><?php echo __('Module'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td><?php echo $this->Html->link($obj['SurveyTemplate']['name'], array('action' => 'view', 'module' => $selectedModule, $obj['SurveyTemplate']['id'])) ?></td>
					<td><?php echo $obj['SurveyTemplate']['description'] ?></td>
					<td><?php echo $obj['SurveyModule']['name'] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>