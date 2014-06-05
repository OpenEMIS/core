<?php 
$fileId = (!isset($fileId))? '1': $fileId; 
$fileId = (isset($multiple))? $fileId : '';
?>
<div id="file-upload-wrapper-<?php echo $fileId;?>" class="form-group">
	<label class="col-md-3 control-label"><?php echo __('File').' '.$fileId; ?></label>
	<div class="col-md-6">
		<div class="fileupload fileupload-new" data-provides="fileupload">
			<div class="input-group">
				<div class="form-control">
					<i class="fa fa-file fileupload-exists"></i> <span class="fileupload-preview"></span>
				</div>
				<div class="input-group-btn">
					<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload"><?php echo __('Remove'); ?></a>
					<span class="btn btn-default btn-file">
						<span class="fileupload-new"><?php echo __('Select file'); ?></span>
						<span class="fileupload-exists"><?php echo __('Change'); ?></span>
						<?php 
							$fileMode = (isset($multiple))? 'files.' : 'file';
							echo $this->Form->input($model.'.'.$fileMode, array('type' => 'file', 'class' => false, 'div' => false, 'label' => false, 'before' => false, 'after' => false, 'between' => false)); ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>