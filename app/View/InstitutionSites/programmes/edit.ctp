<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Programmes'));
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => 'programmes', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => 'programmesEdit'));
$formOptions = $this->FormUtility->getFormOptions(array('action' => 'programmesEdit', $selectedYear));
echo $this->Form->create('InstitutionSiteProgramme', $formOptions);
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-checkable table-input">
		<thead>
			<tr>
				<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
				<th><?php echo __('Programme'); ?></th>
				<th><?php echo __('System'); ?></th>
				<th><?php echo __('Cycle'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php 
			foreach($data as $obj) :
				$checked = false;//!empty($obj[$model]['education_grade_subject_id']) ? 'checked="checked"' : '';
				//$subjectId = $obj['EducationGradesSubject']['id'];
				//if($obj['EducationSubject']['visible']==1 || !empty($checked)) :
					//$name = sprintf('%s.%d.%%s', $model, $i);
			?>
			<tr>
				<td class="checkbox-column">
					<input type="checkbox" class="icheck-input" name="data[AssessmentItem][<?php echo 0; ?>][education_grade_subject_id]" value="<?php echo 0; ?>" <?php echo $checked; ?> />
				</td>
				<td><?php echo $obj['education_programme_name']; ?></td>
				<td><?php echo $obj['education_system_name']; ?></td>
				<td><?php echo $obj['education_cycle_name']; ?></td>
			</tr>
			<?php 
				//endif;
			endforeach;
			?>
		</tbody>
	</table>
</div>

<div class="controls">
	<input type="button" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'programmes', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php $this->end(); ?>
