<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	if(isset($templateData)) {
		if ($_add) {
		    echo $this->Html->link(__('Add'), array('action' => 'add'), array('class' => 'divider'));
		}
		if ($_edit) {
		    echo $this->Html->link(__('Reorder'), array('action' => 'reorder', $templateData['SurveyTemplate']['id']), array('class' => 'divider'));
		    echo $this->Html->link(__('Preview'), array('action' => 'preview', $templateData['SurveyTemplate']['id']), array('class' => 'divider'));
		}
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
			'default' => $selectedModule,
			'div' => 'col-md-3',
			'url' => $this->params['controller'] . '/index',
			'onchange' => 'jsForm.change(this)'
		));

		if(isset($templateOptions)) {
			echo $this->Form->input('survey_template_id', array(
				'class' => 'form-control',
				'label' => false,
				'options' => $templateOptions,
				'default' => $selectedTemplate,
				'div' => 'col-md-3',
				'url' => $this->params['controller'] . '/index/' . $selectedModule,
				'onchange' => 'jsForm.change(this)'
			));
		}
	?>
</div>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered">
		<thead>
			<tr>
				<th class="cell_visible"><?php echo __('Visible'); ?></th>
				<th><?php echo __('Name'); ?></th>
				<th><?php echo __('Field Type'); ?></th>
				<th><?php echo __('Mandatory'); ?></th>
				<th><?php echo __('Unique'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if(isset($data)) : ?>
				<?php foreach ($data as $obj) : ?>
					<tr>
						<td class="cell_visible"><?php echo $this->Utility->checkOrCrossMarker($obj['SurveyQuestion']['visible']==1); ?></td>
						<td><?php echo $this->Html->link($obj['SurveyQuestion']['name'], array('action' => 'view', $obj['SurveyQuestion']['id'])) ?></td>
						<td><?php echo $fieldTypeOptions[$obj['SurveyQuestion']['type']] ?></td>
						<td class="cell_visible">
							<?php
								$arrMandatory = array(2,5,6);
								if(in_array($obj['SurveyQuestion']['type'], $arrMandatory)) {
									echo $this->Utility->checkOrCrossMarker($obj['SurveyQuestion']['is_mandatory']==1);
								} else {
									echo "-";
								}
							?>
						</td>
						<td class="cell_visible">
							<?php
								$arrUnique = array(2,6);
								if(in_array($obj['SurveyQuestion']['type'], $arrUnique)) {
									echo $this->Utility->checkOrCrossMarker($obj['SurveyQuestion']['is_unique']==1);
								} else {
									echo "-";
								}								
							?>
						</td>
					</tr>
				<?php endforeach ?>
			<?php endif ?>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>