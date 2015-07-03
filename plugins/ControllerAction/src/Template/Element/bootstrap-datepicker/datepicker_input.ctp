<div class="input date<?= $attr['null'] == false ? ' required' : '' ?>">
	<label for="<?= $attr['id'] ?>"><?= isset($attr['label']) ? $attr['label'] : $attr['field'] ?></label>
	<div class="input-group date" id="<?= $attr['id'] ?>">
		<?php 

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
		<input type="text" class="form-control" name="<?= $fieldName; ?>" value="<?= isset($attr['value']) ? $attr['value'] : '' ?>" 
		<?php 
			if (array_key_exists('attr', $attr)) {
				echo (array_key_exists('onchange', $attr['attr']))? 'onchange="'.$attr['attr']['onchange'].'"':'';
			}
		 ?>
		/>
		<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
	</div>
	<?php
	if (array_key_exists('fieldName', $attr)) {
		echo $this->Form->error($attr['fieldName']);
	} else {
		echo $this->Form->error($attr['field']);
	}
	?>
</div>
