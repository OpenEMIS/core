<div class="input date<?= $attr['null'] == false ? ' required' : '' ?>">
	<label for="<?= $attr['id'] ?>"><?= $this->ControllerAction->getLabel($attr['model'], $attr['field'], $attr) ?></label>
	<div class="input-group date" id="<?= $attr['id'] ?>">
		<input type="text" class="form-control" name="<?= $attr['model'].'['.$attr['field'].']' ?>" value="<?= isset($attr['value']) ? $attr['value'] : '' ?>" 
		<?php 
			if (array_key_exists('attr', $attr)) {
				echo (array_key_exists('onchange', $attr['attr']))? 'onchange="'.$attr['attr']['onchange'].'"':'';
			}
		 ?>
		/>
		<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
	</div>
	<?= $this->Form->error($attr['field']) ?>
</div>
