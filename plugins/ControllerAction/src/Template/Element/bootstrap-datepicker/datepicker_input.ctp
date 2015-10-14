<?php $label = isset($attr['label']) ? $attr['label'] : $attr['field']; ?>
<?php if ($label): ?>
<div class="input date<?= $attr['null'] == false ? ' required' : '' ?>">
	<label for="<?= $attr['id'] ?>"><?= $label ?></label>
<?php endif; ?>

	<?php 
	$errorMsg = '';
	if (array_key_exists('fieldName', $attr)) {
		$errorMsg = $this->Form->error($attr['fieldName']);
	} else {
		$errorMsg = $this->Form->error($attr['field']);
	}
	$divErrorCSS = (!empty($errorMsg))? 'error': '';
	$inputErrorCSS = (!empty($errorMsg))? 'form-error': '';
	$inputWrapperStyle = (array_key_exists('inputWrapperStyle', $attr)) ? $attr['inputWrapperStyle'] : '';
	?>
	<div class="input-group date <?php echo $divErrorCSS; ?>" id="<?= $attr['id'] ?>" style="<?= $inputWrapperStyle; ?>">
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
		<input type="text" class="form-control <?php echo $inputErrorCSS; ?>" name="<?= $fieldName; ?>" value="<?= isset($attr['value']) ? $attr['value'] : '' ?>" 
		<?php 
			if (array_key_exists('attr', $attr)) {
				echo (array_key_exists('onchange', $attr['attr']))? 'onchange="'.$attr['attr']['onchange'].'"':'';
			}
		 ?>
		/>
		<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
	</div>
	<?php
	echo $errorMsg;
	?>
	
<?php if ($label): ?>
</div>
<?php endif; ?>
