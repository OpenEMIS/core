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
				<th><?php echo __('Cycle'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php 
			foreach($data as $i => $obj) :
				$checked = !empty($obj[$model]['status']);
				if($obj['EducationProgramme']['visible']!=1 && !$checked) {
					continue;
				}
			?>
			<tr>
				<td class="checkbox-column">
					<?php
					echo $this->Form->hidden($i . '.id', array('value' => $obj[$model]['id']));
					echo $this->Form->hidden($i . '.education_programme_id', array('value' => $obj['EducationProgramme']['id']));
					echo $this->Form->checkbox($i . '.status', array('class' => 'icheck-input',	'checked' => $checked));
					?>
				</td>
				<td><?php echo $obj['EducationProgramme']['name']; ?></td>
				<td><?php echo $obj['EducationCycle']['name']; ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => 'programmes', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
