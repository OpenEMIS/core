<?php
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', (!empty($contentHeader) ? $contentHeader : $this->Label->get("$model.title")));
$this->start('contentActions');
echo $this->Html->link($this->Label->get('general.back'), array('action' => $model, 'index', $selectedYear), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('templates/year_options', array('url' => $model . '/edit'));
$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'edit', $selectedYear));
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
				$id = '';
				$checked = 0;
				if (!empty($obj[$model])) {
					$checked = $obj[$model][0]['status'];
					$id = $obj[$model][0]['id'];
				}
			?>
			<tr>
				<td class="checkbox-column">
					<?php
					echo $this->Form->hidden($i . '.id', array('value' => $id));
					echo $this->Form->hidden($i . '.education_programme_id', array('value' => $obj['EducationProgramme']['id']));
					echo $this->Form->checkbox($i . '.status', array('class' => 'icheck-input',	'checked' => $checked));
					?>
				</td>
				<td><?php echo $obj['EducationProgramme']['name']; ?></td>
				<td><?php echo $obj['EducationCycle']['name']; ?></td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => $model, 'index', $selectedYear), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
