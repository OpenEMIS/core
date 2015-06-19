<?= $this->Html->css('OpenEmis.../plugins/icheck/skins/minimal/blue', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/icheck/jquery.icheck.min', ['block' => true]) ?>
<?= $this->Html->script('OpenEmis.../plugins/tableCheckable/jquery.tableCheckable', ['block' => true]) ?>

<?php if ($action == 'add' || $action == 'edit') : ?>

<div class="input clearfix">
	<label class="pull-left" for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="col-md-5">
		<table class="table table-striped table-hover table-bordered table-checkable">
			<thead>
				<tr>
					<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
					<th><?= __('Education Subject') ?></th>
					<th><?= __('Class') ?></th>
					<th><?= __('Teacher') ?></th>
				</tr>
			</thead>

			<?php if (isset($attr['data'])) : ?>
			<?php //pr($attr['data']['teachers']);?>
			<tbody>
				<?php foreach ($attr['data']['subjects'] as $i=>$obj) : ?>
					<?php //pr($obj);?>
				<?php 	$selected = false;//(isset($attr['selected']) && in_array($obj->education_grade_id, $attr['selected'])) ? true : false; ?>
				<tr>
					<td class="checkbox-column">
						<input type="checkbox" class="icheck-input" name="<?php echo sprintf('MultiClasses[%d][id]', $i) ?>" value="<?php echo $obj->id?>" <?php echo ($selected) ? 'checked' : '';?> />
					</td>
					<td><?= $obj->education_subject->name ?></td>
					<td><?= $obj->education_subject->name ?></td>
					<td><?php 
					echo $this->Form->input(sprintf('MultiSections.%d.security_user_id', $i), array(
						'options' => $attr['data']['teachers'], 
						'label' => false,
					));
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
