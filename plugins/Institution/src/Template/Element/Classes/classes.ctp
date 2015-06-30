<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>
	
<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->Label->get($attr['model'] .'.'. $attr['field']) ?></label>
	<div class="table-in-view col-md-5 table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?= $this->Label->get($attr['model'] .'.education_subject') ?></th>
					<th><?= $this->Label->get($attr['model'] .'.class') ?></th>
					<th><?= $this->Label->get($attr['model'] .'.teacher') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>
			<?php //pr($attr['data']['existedSubjects']);?>
			<tbody>
				<?php foreach ($attr['data']['subjects'] as $i=>$obj) : ?>
					<?php $n = intval($obj->education_subject->id) ?>
					<?php //pr($obj->toArray());?>
					<?php 
						$selected = (isset($attr['data']['existedSubjects']) && array_key_exists($n, $attr['data']['existedSubjects'])) ? 'checked' : ''; 
						if ($selected) {
							$disabled = 'disabled';
						} else {
							$disabled = '';
						}
					?>
				<tr>
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?php echo sprintf('MultiClasses[%d][education_subject_id]', $i) ?>" value="<?php echo $n?>" <?php echo $selected;?> <?php echo $disabled;?> />
					</td>
					<td><?= $obj->education_subject->name ?></td>
					<td>
						<input type="text" name="<?php echo sprintf('MultiClasses[%d][name]', $i) ?>" value="<?php echo $obj->education_subject->name ?>" <?php echo $disabled;?> />
					</td>
					<td>
						<input type="hidden" name="<?php echo sprintf('MultiClasses[%d][institution_site_class_staff][0][status]', $i) ?>" value="1" />
					<?php 
					if (!$selected) {
						echo $this->Form->input(sprintf('MultiClasses.%d.institution_site_class_staff.0.security_user_id', $i), array(
							'options' => $attr['data']['teachers'], 
							'label' => false,
						));
					}
					?></td>
				</tr>
				<?php endforeach ?>
			</tbody>
			<?php endif ?>
		</table>
	</div>
</div>

<?php else : ?>

<?php 
	foreach ($attr['data']['grades'] as $grade) {
		// pr($grade);die;
		echo $grade->name.'<br/>';
	}
	// pr($attr['data']['grades']);
?>

<?php endif ?>
