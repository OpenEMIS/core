<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $this->data[$model]['name']);

$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => $_action . 'View', $this->data[$model]['id']), array('class' => 'divider'));
$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => $_action . 'Edit', $this->data[$model]['id']));
echo $this->Form->create($model, $formOptions);
echo $this->Form->hidden('id');
echo $this->Form->hidden('school_year_id');
echo $this->Form->input('year', array('value' => $this->data['SchoolYear']['name'], 'disabled' => 'disabled'));
echo $this->Form->input('name');
echo $this->Form->input('no_of_seats');
echo $this->Form->input('no_of_shifts', array('options' => $shiftOptions));
?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('EducationGrade.name'); ?></label>
	<div class="col-md-8">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered table-checkable table-input">
				<thead>
					<tr>
						<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
						<th><?php echo $this->Label->get('EducationProgramme.name'); ?></th>
						<th><?php echo $this->Label->get('EducationGrade.name'); ?></th>
					</tr>
				</thead>
				
				<tbody>
					<?php 
					$i = 0;
					foreach($grades as $obj) :
						$checked = $obj['InstitutionSiteClassGrade']['status'];
					?>
					<tr>
						<td class="checkbox-column">
							<?php
							echo $this->Form->hidden('InstitutionSiteClassGrade.' . $i . '.id', array('value' => $obj['InstitutionSiteClassGrade']['id']));
							echo $this->Form->hidden('InstitutionSiteClassGrade.' . $i . '.institution_site_class_id', array('value' => $this->data[$model]['id']));
							echo $this->Form->hidden('InstitutionSiteClassGrade.' . $i . '.education_grade_id', array('value' => $obj['EducationGrade']['id']));
							echo $this->Form->checkbox('InstitutionSiteClassGrade.' . $i++ . '.status', array('class' => 'icheck-input', 'checked' => $checked));
							?>
						</td>
						<td><?php echo $obj['EducationProgramme']['name']; ?></td>
						<td><?php echo $obj['EducationGrade']['name']; ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

	</div>
</div>

<?php
echo $this->FormUtility->getFormButtons(array('cancelURL' => array('action' => $_action . 'View', $this->data[$model]['id'])));
echo $this->Form->end();

$this->end(); 
?>
