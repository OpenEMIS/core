<?php
$fieldName = '%s[%s]';
if (isset($attr['null']) && empty($attr['null'])) {
	$required = 'required';
} else {
	$required = '';
}
?>

<div class="input file <?= $required ?>">
	<label><?= !empty($attr['label']) ? $attr['label'] : __('File') ?></label>
	<?php if (!empty($attr['value'])) : ?>
		<div class="fileinput fileinput-exists input-group" data-provides="fileinput">
	<?php else : ?>
		<div class="fileinput fileinput-new input-group" data-provides="fileinput">
	<?php endif ?>
			<div class="form-control" data-trigger="fileinput">
				<i class="fa fa-file-o fileinput-exists"></i>
				<span class="fileinput-filename"><?= !empty($attr['value']) ? $attr['value'] : ''; ?></span>
			</div>
			<a href="#" class="input-group-addon btn btn-default fileinput-exists btn-file-cancel" data-dismiss="fileinput"><i class="fa fa-trash"></i></a>
			<span class="input-group-addon btn btn-default btn-file">
				<span class="fileinput-new"><i class="fa fa-folder"></i></span>
				<span class="fileinput-exists fa fa-folder"></span>
				<input type="file" name="<?= sprintf($fieldName, $attr['model'], $attr['field']) ?>" class="fa fa-folder">
			</span>
			<div class="file-input-text">
		   		<p><?= $attr['comment'] ?></p>
			</div>
		</div>
		<?= $this->Form->error($attr['field']);?>
</div>
