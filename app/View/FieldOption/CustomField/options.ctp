<?php 
echo $this->Html->css('../js/plugins/icheck/skins/minimal/blue', 'stylesheet', array('inline' => false));
echo $this->Html->script('plugins/tableCheckable/jquery.tableCheckable', false);
echo $this->Html->script('plugins/icheck/jquery.icheck.min', false);

$defaults = $this->FormUtility->getFormDefaults();
$type = false;
if ($this->action == 'view') {
	$type = $data[$model]['type'] == 3 || $data[$model]['type'] == 4;
} else {
	$type = !empty($this->data) && ($this->data[$model]['type'] == 3 || $this->data[$model]['type'] == 4);
}
$modelOption = $model . 'Option';
$parentId = Inflector::underscore($model) . '_id';

// hidden reload submit button to updating form inputs
echo $this->Form->button('reload', array('id' => 'reload', 'type' => 'submit', 'name' => 'submit', 'value' => 'reload', 'class' => 'hidden'));

if ($type) : // type is dropdown (3) or multiple checkboxes (4)
	if ($this->action == 'add' || $this->action == 'edit') : 
		echo $this->Form->button($modelOption, array('id' => $modelOption, 'type' => 'submit', 'name' => 'submit', 'value' => $modelOption, 'class' => 'hidden'));
?>
	
	<div class="form-group">
		<label class="<?php echo $defaults['label']['class'] ?>"><?php echo $this->Label->get('general.options') ?></label>
		<div class="col-md-6">
			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered table-checkable table-input">
					<thead>
						<tr>
							<?php if ($this->action == 'edit') : ?>
							<th class="checkbox-column"><input type="checkbox" class="icheck-input" /></th>
							<?php endif ?>
							<th><?php echo $this->Label->get('general.name') ?></th>
							<?php if ($this->action == 'add') : ?>
							<th class="cell-delete"></th>
							<?php endif ?>
						</tr>
					</thead>
					<tbody>
					<?php 
					if (($this->action == 'add' || $this->action == 'edit') && isset($this->data[$modelOption])) :
						foreach ($this->data[$modelOption] as $i => $obj) :
					?>
						<tr>
							<?php 
							// if 'edit' then show checkboxes
							echo $this->Form->hidden("$modelOption.$i.order");
							if ($this->action == 'edit') {
								$checked = $obj['visible'] == 1;
								
								echo '<td class="checkbox-column" style="padding-top: 11px;">';
								echo $this->Form->hidden("$modelOption.$i.id");
								echo $this->Form->hidden("$modelOption.$i.$parentId", array('value' => $this->data[$model]['id']));
								echo $this->Form->checkbox("$modelOption.$i.visible", array('class' => 'icheck-input', 'checked' => $checked));
								echo '</td>';
							}
							?>
							<td><?php echo $this->Form->input("$modelOption.$i.value", array('label' => false, 'div' => false, 'between' => false, 'after' => false)) ?></td>
							
							<?php if ($this->action == 'add') : ?>
							<!-- if 'add' then show delete button -->
							<td><span class="icon_delete" title="<?php echo $this->Label->get('general.delete') ?>" onclick="jsTable.doRemove(this)"></span></td>
							<?php endif ?>
						</tr>
					<?php
						endforeach;
					endif;
					?>
					</tbody>
				</table>
				<a class="void icon_plus" onclick="$('#<?php echo $modelOption ?>').click()"><?php echo $this->Label->get('general.add') ?></a>
			</div>
		</div>
	</div>
	
	<?php else : ?>
	
	<?php if (!empty($data[$modelOption])) { ?>
	<div class="table-responsive">
		<table class="table table-striped table-hover table-bordered table-checkable table-input">
			<thead>
				<tr>
					<th class="checkbox-column"></th>
					<th><?php echo $this->Label->get('general.option') ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($data[$modelOption] as $i => $obj) :	?>
				<tr>
					<td class="checkbox-column center"><?php echo $this->Utility->checkOrCrossMarker($obj['visible']) ?></td>
					<td><?php echo $obj['value'] ?></td>
				</tr>	
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
	<?php 
	} else {
		echo $this->Label->get('general.noOptions');
	}
	?>
	
<?php 
	endif;
endif;
?>
