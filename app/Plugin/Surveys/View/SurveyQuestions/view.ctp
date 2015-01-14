<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $contentHeader);
$this->start('contentActions');
	$params = $this->params->named;
	echo $this->Html->link(__('Back'), array_merge(array('action' => 'index'), $params), array('class' => 'divider'));
	if ($_edit) {
		echo $this->Html->link(__('Edit'), array_merge(array('action' => 'edit', $data[$Custom_Field]['id']), $params), array('class' => 'divider'));
	}
	if ($_delete) {
		echo $this->Html->link(__('Delete'), array('action' => 'delete'), array('class' => 'divider', 'onclick' => 'return jsForm.confirmDelete(this)'));
	}
$this->end();

$this->start('contentBody');
?>

	<div class="row">
		<div class="col-md-3"><?php echo __('Name'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyTemplate']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Field Name'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyQuestion']['name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Field Type'); ?></div>
		<div class="col-md-6"><?php echo $fieldTypeOptions[$data['SurveyQuestion']['type']]; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Mandatory'); ?></div>
		<div class="col-md-6"><?php echo $mandatoryOptions[$data['SurveyQuestion']['is_mandatory']]; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Unique'); ?></div>
		<div class="col-md-6"><?php echo $uniqueOptions[$data['SurveyQuestion']['is_unique']]; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Visible'); ?></div>
		<div class="col-md-6"><?php echo $visibleOptions[$data['SurveyQuestion']['visible']]; ?></div>
	</div>
	<?php if (sizeof($data['SurveyQuestionChoice']) > 0) : ?>
		<div class="row">
			<div class="col-md-3"><?php echo __('Choice'); ?></div>
			<div class="col-md-6">
				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered table-checkable table-input">
						<thead>
							<tr>
								<th class="checkbox-column"></th>
								<th><?php echo __('Value'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
								foreach ($data['SurveyQuestionChoice'] as $i => $obj) :
							?>
								<tr>
									<td class="checkbox-column center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']) ?></td>
									<td><?php echo $obj['value'];?></td>
								</tr>
							<?php
								endforeach;
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	<?php endif ?>
	<?php if (sizeof($data['SurveyTableColumn']) > 0 || sizeof($data['SurveyTableRow']) > 0) : ?>
		<div class="row">
			<fieldset class="section_group">
				<legend><?php echo $data['SurveyQuestion']['name']; ?></legend>
				<div class="table-responsive">
					<table class="table table-striped table-hover table-bordered">
						<thead>
							<tr>
								<th></th>
								<?php
								if(isset($data['SurveyTableColumn'])) :
									foreach ($data['SurveyTableColumn'] as $i => $obj) {
										if($obj['visible'] == 1) :
								?>
										<th><?php echo $obj['name']; ?></th>
								<?php
										endif;
									}
								endif;
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							if(isset($data['SurveyTableRow'])) :
								foreach ($data['SurveyTableRow'] as $i => $obj) {
									if($obj['visible'] == 1) :
							?>
									<tr>
										<td><?php echo $obj['name']; ?></td>
										<?php
										if(isset($data['SurveyTableColumn'])) :
											foreach ($data['SurveyTableColumn'] as $j => $obj) {
												if($obj['visible'] == 1) :
										?>
												<td></td>
										<?php
												endif;
											}
										endif;
										?>
									</tr>
							<?php
									endif;
								}
							endif;
							?>
						</tbody>
					</table>
				</div>
			</fieldset>
		</div>
	<?php endif ?>
	<div class="row">
		<div class="col-md-3"><?php echo __('Modified by'); ?></div>
		<div class="col-md-6"><?php echo $data['ModifiedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Modified on'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyQuestion']['modified']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Created by'); ?></div>
		<div class="col-md-6"><?php echo $data['CreatedUser']['first_name'] . " " . $data['ModifiedUser']['last_name']; ?></div>
	</div>
	<div class="row">
		<div class="col-md-3"><?php echo __('Created on'); ?></div>
		<div class="col-md-6"><?php echo $data['SurveyQuestion']['created']; ?></div>
	</div>

<?php $this->end(); ?>