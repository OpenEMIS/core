<?php
$fieldName = '%s[%s]';
?>

<div class="input file">
	<label><?= __('File') ?></label>

	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
		<div class="form-control" data-trigger="fileinput">
			<i class="fa fa-file-o fileinput-exists"></i> <span class="fileinput-filename"></span>
			<a href="#" class="input-group-addon btn btn-default fileinput-exists btn-file-cancel" data-dismiss="fileinput"><?= __('<i class="fa fa-close"></i> ') ?></a>
			<span class="input-group-addon btn btn-default btn-file">
				<span class="fileinput-new fa fa-folder"><?= __('') ?></span>
				<span class="fileinput-exists fa fa-folder"><?= __('') ?></span>
				<input type="file" name="<?= sprintf($fieldName, $attr['model'], $attr['field']) ?>">
			</span>	
		</div>
	</div>
</div>