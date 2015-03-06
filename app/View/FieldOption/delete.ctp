<?php
$this->extend('/Elements/layout/container');
$this->assign('contentHeader', $header);

$this->start('contentActions');
$params = array('action' => 'view', $selectedOption, $selectedValue);
if(isset($conditionId)) {
	$params = array_merge($params, array($conditionId => $selectedSubOption));
}
echo $this->Html->link($this->Label->get('general.back'), $params, array('class' => 'divider'));

$this->end();

$this->start('contentBody');

$formOptions = $this->FormUtility->getFormOptions(array('controller' => $this->params['controller'], 'action' => 'delete', $selectedOption, $selectedValue));
$labelOptions = $formOptions['inputDefaults']['label'];
echo $this->Form->create($model, $formOptions);

echo $this->Form->input('convert_from', array('value' => $currentFieldOptionValue[$model]['name'], 'disabled' => true));
echo $this->Form->input('convert_to', array('options' => $allOtherFieldOptionValues));
?>

<div class="form-group">
	<label class="col-md-3 control-label"><?php echo $this->Label->get('FieldOption.apply') ?></label>
	<div class="col-md-6">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-bordered">
				<thead>
					<tr>
						<th><?php echo $this->Label->get('FieldOption.module') ?></th>
						<th><?php echo $this->Label->get('FieldOption.records') ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($modifyForeignKey as $key => $value) : ?>
					<tr>
						<td><?php echo $key ?></td>
						<td class="cell-number"><?php echo $value ?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="form-group">
	<div class="col-md-offset-4">
		<input type="submit" value="<?php echo $this->Label->get('general.delete'); ?>" class="btn_save btn_right"/>
		<?php echo $this->Html->link($this->Label->get('general.cancel'), array('action' => 'view', $selectedOption, $selectedValue), array('class' => 'btn_cancel btn_left')); ?>
	</div>
</div>
<?php 
echo $this->Form->end();
$this->end();
?>
