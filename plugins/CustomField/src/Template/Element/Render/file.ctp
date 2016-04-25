<?php
// field name
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
// End

if (isset($attr['null']) && empty($attr['null'])) {
	$required = 'required';
} else {
	$required = '';
}
if (isset($attr['label'])){
	if (!empty($attr['label'])) {
		$label = __($attr['label']);
	} else {
		$label = false;
	}
} else {
	$label = __('File');
}
$startWithOneLeftButton = false;
$selectedButton = 'import'; // either import or download
if (isset($attr['startWithOneLeftButton'])) {
	if (!empty($attr['startWithOneLeftButton']) && $attr['startWithOneLeftButton']) {
		$startWithOneLeftButton = true;
		$selectedButton = !is_bool($attr['startWithOneLeftButton']) ? $attr['startWithOneLeftButton'] : $selectedButton;
	}
}
$startWithTwoLeftButton = false;
if (isset($attr['startWithTwoLeftButton'])) {
	if (!empty($attr['startWithTwoLeftButton']) && $attr['startWithTwoLeftButton']) {
		$startWithTwoLeftButton = true;
		$selectedButton = !is_bool($attr['startWithTwoLeftButton']) ? $attr['startWithTwoLeftButton'] : $selectedButton;
	}
}
if ($startWithTwoLeftButton) {
	$wrapperClass = 'input-double-btn';
} else if ($startWithOneLeftButton) {
	$wrapperClass = 'input-single-btn';
} else {
	$wrapperClass = '';
}
// Notes to developers: both the startWithOneLeftButton and startWithTwoLeftButton cannot be set to true, else startWithTwoLeftButton will be active instead.
if (isset($attr['alwaysShowOneButton'])) {
	if (!empty($attr['alwaysShowOneButton']) && $attr['alwaysShowOneButton'] && !empty($wrapperClass)) {
	 	$wrapperClass .= ' always-single';
	}
} else if (!empty($wrapperClass)) {
	$wrapperClass .= ' always-single';
}
?>

<div class="input file <?= $required ?>">

	<?php if ($label): ?>
	<label><?= $label ?></label>
	<?php endif; ?>

	<?php if (!empty($attr['value'])) : ?>
		<div class="fileinput fileinput-exists input-group <?= $wrapperClass ?>" data-provides="fileinput">
	<?php else : ?>
		<div id="file-input-wrapper" class="fileinput fileinput-new input-group <?= $wrapperClass ?>" data-provides="fileinput">
	<?php endif ?>

		<?php if (!empty($wrapperClass)):?>
			<div class="input-left-btn">
				<?php 
					$downloadClass = ($selectedButton=='download') ? '' : 'fileinput-exists ';
				?>
				<?php 
					if (!isset($downloadOnClick)):
						$downloadOnClick = "";
					endif;
				?>
				<button class="btn <?= $downloadClass; ?>" data-toggle="tooltip" data-container="body" data-placement="bottom" title="<?= __('Download Template') ?>" type="reset" onclick="<?= $downloadOnClick ?>">
					<i class="fa kd-download"></i>
				</button>
				
				<?php 
					$importClass = ($selectedButton=='import') ? '' : 'fileinput-exists ';
				?>
				<?php 
					if (!isset($importOnClick)):
						$importOnClick = '';
					endif;
				?>
				<button class="btn <?= $importClass; ?>" data-toggle="tooltip" data-container="body" data-placement="bottom" title="<?= __('Import') ?>" type="reset" onclick="<?= $importOnClick ?>"><i class="fa kd-import"></i></button>

			</div>
		<?php endif; ?>


			<div class="form-control" data-trigger="fileinput">
				<i class="fa fa-file-o fileinput-exists"></i>
				<span class="fileinput-filename"><?= !empty($attr['value']) ? $attr['value'] : ''; ?></span>
			</div>

			<a href="#" class="input-group-addon btn fileinput-exists btn-file-cancel" data-dismiss="fileinput" data-toggle="tooltip" data-container="body" data-placement="bottom" title="<?=__('Remove') ?>"><i class="fa fa-close"></i></a>
			<div class="input-group-addon btn btn-default btn-file" data-toggle="tooltip" data-container="body" data-placement="bottom" title="<?=__('Browse') ?>">
				<span class="fileinput-new"><i class="fa fa-folder"></i></span>
				<span class="fileinput-exists fa fa-folder"></span>
				<input type="file" name="<?= $fieldName; ?>" class="fa fa-folder">
			</div>

			<div class="file-input-text">
		   		<p><?= $attr['comment'] ?></p>
			</div>
		</div>

		<?php
			$errorMsg = '';
			if (array_key_exists('fieldName', $attr)) {
				$errorMsg = $this->Form->error($attr['fieldName']);
			} else {
				$errorMsg = $this->Form->error($attr['field']);
			}
			echo $errorMsg;
		?>

</div>

<script>
$(document).ready(function(e) {
	if ($("#file-input-wrapper").hasClass("always-single")) {
		$("#file-input-wrapper").find('button.btn.fileinput-exists').removeClass('fileinput-exists').addClass('hidden');
	}
	$("#file-input-wrapper").on('change.bs.fileinput', function(e){
		if ($(this).hasClass("input-single-btn") && !$(this).hasClass("always-single")) {
			$(this).removeClass("input-single-btn").addClass("input-double-btn");
		}
	}).on('clear.bs.fileinput', function(e){
		if ($(this).hasClass("input-double-btn")) {
			$(this).removeClass("input-double-btn").addClass("input-single-btn");
		}
	});
});
</script>
