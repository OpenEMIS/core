<div class="input">
	<label for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="input-group date" id="<?= $attr['id'] ?>">
		<input type="text" class="form-control" name="<?= $attr['model'].'['.$attr['field'].']' ?>" />
		<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
	</div>
</div>
