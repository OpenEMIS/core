<div class="input time<?= $attr['null'] == false ? ' required' : '' ?>">
	<label for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="input-group time" id="<?= $attr['id'] ?>">
		<input type="text" class="form-control" name="<?= $attr['model'].'['.$attr['field'].']' ?>" value="<?= isset($attr['value']) ? $attr['value'] : '' ?>" />
		<span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
	</div>
	<?= $this->Form->error($attr['field']) ?>
</div>
