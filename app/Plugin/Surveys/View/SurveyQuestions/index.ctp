<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	echo $this->Html->link(__('Back'), array('plugin' => false, 'controller' => 'SurveyTemplates', 'action' => 'view', $templateData['SurveyTemplate']['id']), array('class' => 'divider'));
	if ($_add) {
	    echo $this->Html->link(__('Add'), array('action' => 'add'), array('class' => 'divider'));
	}
	if ($_edit && !empty($data)) {
	    echo $this->Html->link(__('Reorder'), array('action' => 'reorder', $templateData['SurveyTemplate']['id']), array('class' => 'divider'));
	    echo $this->Html->link(__('Preview'), array('action' => 'preview', $templateData['SurveyTemplate']['id']), array('class' => 'divider'));
	}
$this->end();

$this->start('contentBody');
?>
<?php echo $this->element('alert'); ?>

<div class="row form-group input">
	<label class="col-md-3 control-label"><?php echo __('Name'); ?></label>
	<div class="col-md-4">
		<?php
			echo $templateData['SurveyTemplate']['name'];
		?>
	</div>
</div>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell_visible"><?php echo __('Visible'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Field Type'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($data as $obj) : ?>
				<tr>
					<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['SurveyQuestion']['visible']==1); ?></td>
					<td><?php echo $this->Html->link($obj['SurveyQuestion']['name'], array('action' => 'view', $obj['SurveyQuestion']['id'])) ?></td>
					<td><?php echo $fieldTypeOptions[$obj['SurveyQuestion']['type']] ?></td>
				</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>