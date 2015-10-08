<?php
$fieldName = '%s[%s]';
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
?>

<div class="input file <?= $required ?>">
	<?php if ($label): ?>
	<label><?= $label ?></label>
	<?php endif; ?>

	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
		<div class="form-control" data-trigger="fileinput">
			<i class="fa fa-file-o fileinput-exists"></i> 
			<span class="fileinput-filename"></span>
		</div>	
		<a href="#" class="input-group-addon btn btn-default fileinput-exists btn-file-cancel" data-dismiss="fileinput"><?= '<i class="fa fa-trash"></i> ' ?></a>
		<span class="input-group-addon btn btn-default btn-file">
			<span class="fileinput-new"><i class="fa fa-folder"></i><?= __('') ?></span>
			<span class="fileinput-exists fa fa-folder"><?= __('') ?></span>
			<input type="file" name="<?= sprintf($fieldName, $attr['model'], $attr['field']) ?>" class="fa fa-folder">
		</span>
		<div class="file-input-text">
			<p><?= $attr['comment'] ?></p>
		</div>	
	</div>

	<?php echo $this->Form->error($attr['field']);?>

</div>
