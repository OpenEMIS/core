<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);
$this->start('contentActions');
echo $this->Html->link(__('View'), array('action' => $model, 'index'), array('class' => 'divider'));
$this->end();

$this->start('contentBody');
echo $this->element('../InstitutionSites/InstitutionSiteSection/controls');
$formOptions = $this->FormUtility->getFormOptions(array('action' => $model, 'edit'));
echo $this->Form->create($model, $formOptions);
?>

<div class="table-responsive">
	<table class="table table-striped table-hover table-bordered table-checkable table-input">
		<thead>
			<tr>
				<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
				<th><?php echo $this->Label->get('general.openemisId'); ?></th>
				<th><?php echo $this->Label->get('general.name'); ?></th>
				<th><?php echo $this->Label->get('general.category'); ?></th>
			</tr>
		</thead>
		
		<tbody>
			<?php 
			foreach($data as $i => $obj) :
				$checked = !empty($obj[$model]['status']) ? true : false;
			?>
			<tr>
				<td class="checkbox-column">
					<?php
					echo $this->Form->hidden($i . '.id', array('value' => $obj[$model]['id']));
					echo $this->Form->hidden($i . '.student_id', array('value' => $obj['Student']['id']));
					echo $this->Form->hidden($i . '.institution_site_section_id', array('value' => $obj['InstitutionSiteSection']['id']));
					echo $this->Form->hidden($i . '.education_grade_id', array('value' => $selectedGrade));
					echo $this->Form->checkbox($i . '.status', array('class' => 'icheck-input', 'checked' => $checked));
					?>
				</td>
				<td><?php echo $obj['Student']['identification_no']; ?></td>
				<td><?php echo $obj['Student']['first_name'] . ' ' . $obj['Student']['last_name']; ?></td>
				<td>
					<?php
					echo $this->Form->input($i . '.student_category_id', array(
						'label' => false,
						'div' => false,
						'before' => false,
						'between' => false,
						'after' => false,
						'options' => $categoryOptions,
						'value' => $obj[$model]['student_category_id']
					));
					?>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<div class="controls">
	<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
	<?php echo $this->Html->link(__('Cancel'), array('action' => $model, 'index'), array('class' => 'btn_cancel btn_left')); ?>
</div>

<?php echo $this->Form->end(); ?>
<?php $this->end(); ?>
