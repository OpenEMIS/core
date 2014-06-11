<?php
$style = 'width: ' . $width . 'px; height: ' . $height . 'px';
$class = !empty($src) ? 'form-group fileupload fileupload-exists' : 'form-group fileupload fileupload-new';
?>

<div class="<?php echo $class ?>" data-provides="fileupload">
	<label class="col-md-3 control-label"><?php echo $label; ?></label>
	<div class="col-md-4">
		<div class="fileupload-new thumbnail" style="<?php echo $style ?>">
			<img data-src="<?php echo sprintf('holder.js/%sx%s', $width, $height) ?>" />
		</div>
		<div class="fileupload-preview fileupload-exists thumbnail" style="max-width: <?php echo $width ?>px; max-height: <?php echo intval($height)+10 ?>px; line-height: 20px;">
			<?php
			if(!empty($src)) {
				echo sprintf('<img style="max-height: %spx" src="%s" />', $height, $src);
			}
			?>
		</div>
		<div>
			<span class="btn btn-default btn-file"><span class="fileupload-new"><?php echo __('Select Image') ?></span>
			<span class="fileupload-exists"><?php echo __('Change') ?></span>
				<!--input type="file" /-->
				<?php 
				echo $this->Form->input($model.'.'.$field, array(
					'type' => 'file', 
					'class' => false, 
					'div' => false, 
					'label' => false, 
					'before' => false, 
					'after' => false, 
					'between' => false,
					'error' => false
				)); 
				?>
			</span>
			<a href="#" class="btn btn-default fileupload-exists" data-dismiss="fileupload"><?php echo __('Remove') ?></a>
		</div>
	</div>
	<?php echo $this->Form->error($model.'.'.$field, null, array('class' => 'error-message')); ?>
</div>
