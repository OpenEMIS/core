<div class="input time<?= $attr['null'] == false ? ' required' : '' ?>">
	<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
	<div class="input-group time" id="<?= $attr['id'] ?>">
		<?php 
			$errorMsg = '';
			if (array_key_exists('fieldName', $attr)) {
				$errorMsg = $this->Form->error($attr['fieldName']);
			} else {
				$errorMsg = $this->Form->error($attr['field']);
			}
			$fieldName = (array_key_exists('fieldName', $attr))? $attr['fieldName']: $attr['model'].'['.$attr['field'].']';
			// need to format this string
			$tokens = explode('.', $fieldName);
			$firstToken = array_shift($tokens);
			foreach ($tokens as $key => $value) {
				$tokens[$key] = '['.$value.']';
			}
			$tokens = array_reverse($tokens);
			$tokens[] = $firstToken;
			$tokens = array_reverse($tokens);
			$fieldName = implode('', $tokens);
		 ?>
		<input type="text" class="form-control" name="<?= $fieldName; ?>" value="<?= isset($attr['value']) ? $attr['value'] : '' ?>" />
		<span class="input-group-addon"><i class="glyphicon glyphicon-time"></i></span>
	</div>
	<?php echo $errorMsg ?>
</div>
