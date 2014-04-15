<div class="form-group">
	<label class="col-md-3 control-label"><?php echo __('File'); ?></label>
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
						<?php echo $this->Form->input('file', array('type' => 'file', 'class' => false, 'div' => false, 'label' => false, 'before' => false, 'after' => false, 'between' => false)); ?>
					</span>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="form-group">
	<label class="col-md-3 control-label">&nbsp;</label>
	<div class="col-md-7">
		<em><?php echo __('*File size should not be larger than 2MB.');?></em>
	</div>
</div>