<?php
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
		<div id="file-input-wrapper" class="fileinput fileinput-exists input-group <?= $wrapperClass ?>" data-provides="fileinput">
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
				<button class="btn <?= $downloadClass; ?>" data-toggle="tooltip" data-container="body" data-placement="bottom" title="<?= __('Download Template') ?>" type="button" onclick="<?= $downloadOnClick ?>">
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
				<?php $this->Form->unlockField($attr['model'].'.'.$attr['field']); ?>
				<?=	$this->Form->file($attr['model'].'.'.$attr['field'], [
						'class' => 'fa fa-folder'
					]) 
				?>
			</div>

			<div class="file-input-text">
		   		<p><?= $attr['comment'] ?></p>
			</div>
		</div>
		<?= $this->Form->error($attr['field']);?>

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
