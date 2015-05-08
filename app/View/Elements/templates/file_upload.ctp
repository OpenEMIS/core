<?php
echo $this->Html->css('../js/plugins/fileupload/bootstrap-fileupload', array('inline' => false));
echo $this->Html->script('plugins/fileupload/bootstrap-fileupload', false);

if(!isset($label)) {
	$label = __('File');
}
echo $this->element('templates/file_upload_field', compact('multiple', 'fileId', 'label'));
?>

<?php if(isset($multiple)) :?>
<div class="form-group">
	<label class="col-md-3 control-label">&nbsp;</label>
	<div class="col-md-7"><a class="void icon_plus" onclick="jsForm.insertNewInputFile(this)" multipleURL='<?php echo $multiple['multipleURL'];?>'><?php echo __($this->Label->get('general.add')); ?></a></div>
</div>
<?php endIf; ?>

<div class="form-group">
	<label class="col-md-3 control-label">&nbsp;</label>
	<div class="col-md-7">
		<em><?php echo (isset($multiple)) ? $this->Label->get('fileUpload.multi') : $this->Label->get('fileUpload.single'); ?></em>
	</div>
</div>