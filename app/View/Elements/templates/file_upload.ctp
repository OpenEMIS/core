<?php echo $this->element('templates/file_upload_field', compact('multiple', 'fileId')); ?>

<?php if(isset($multiple)) :?>
<div class="form-group">
	<label class="col-md-3 control-label">&nbsp;</label>
	<div class="col-md-7"><a class="void icon_plus" multipleURL='<?php echo $multiple['multipleURL'];?>'><?php echo __($this->Label->get('general.add')); ?></a></div>
</div>
<?php endIf; ?>

<div class="form-group">
	<label class="col-md-3 control-label">&nbsp;</label>
	<div class="col-md-7">
		<em><?php echo (isset($multiple)) ? $this->Label->get('fileUpload.multi') : $this->Label->get('fileUpload.single'); ?></em>
	</div>
</div>