<?php
$fieldName = '%s[%s]';
?>

<div class="input file">
	<label><?= __('File') ?></label>

	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
		<div class="form-control" data-trigger="fileinput">
			<i class="glyphicon glyphicon-file fileinput-exists"></i> <span class="fileinput-filename"></span>
		</div>
		<span class="input-group-addon btn btn-default btn-file">
			<span class="fileinput-new"><?= __('Select file') ?></span>
			<span class="fileinput-exists"><?= __('Change') ?></span>
			<input type="file" name="<?= sprintf($fieldName, $attr['model'], $attr['field']) ?>">
		</span>
		<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput"><?= __('Remove') ?></a>
	</div>
</div>
