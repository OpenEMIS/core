<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));
echo $this->Html->css('imgareaselect-default.css', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.imgareaselect.pack.js', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="site" class="content_wrapper dashboard_wrapper">
	<h1>
		<span><?php echo __('Dashboard Image'); ?></span>
		<?php echo $this->Html->link(__('Back to Config'), '/Config', array('class' => 'divider', 'id' => 'back_to_config')); ?>
		<!-- <a class="void link-edit divider">Edit</a> -->
	</h1>
	<?php echo $this->element('alert'); ?>
	<!-- <fieldset class="section_break">
		<legend>Dashboard</legend>
	</fieldset> -->
	
	<div id="loader" class="loading">
		<?php //echo $this->Html->image($imageConfig['image_orignal'], array('alt' => 'Dashboard', 'width' => "{$imageConfig['width']}px", 'id'=>'dashboard_image'))?>
	</div>
	<br/><br/>
	<?php
	echo $this->Form->create('', array('type' => 'file'));
	echo $this->Form->file('image');
	?>

	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	<input type="hidden" name="data[ConfigItem][x]" id="x_coord" value='<?php echo $imageConfig['dashboard_img_x_offset']; ?>' />
	<input type="hidden" name="data[ConfigItem][y]" id="y_coord" value='<?php echo $imageConfig['dashboard_img_y_offset']; ?>' />
	<input type="hidden" name="data[ConfigItem][action]" id="action" value='edit' />
	<?php echo $this->Form->end(); ?>
</div>

<?php //echo $this->element('sql_dump'); ?>

<script type="text/javascript">
	$(document).ready(function(){

	    var img = new Image();
	    $(img).load(function(){
	    	$(this).hide();
	    	$('#loader').removeClass('loading').append(this);
	    	$(this).show();
	    	loadAreaSelect();

	    })
	    .error(function(){
	    	alert('Unable to load image.');
	    })
	    .attr('src', '<?php echo "../img/".$imageConfig['dashboard_img_folder']."/".$imageConfig['dashboard_img_file']; ?>')
	    .attr('id', 'dashboard_image');

		$('#ConfigItemImage').change(function(){
			if($(this).val() !== ''){
				$('.btn_save').val('Upload');
				$('#action').val('add');

			}else{
				$('.btn_save').val('Save');
				$('#action').val('edit');

			}
		});

		$('.btn_save').click(function(event){
			if($('#action').val() === 'edit'){
				// event.preventDefault();
				var x = $('#x_coord').val(),
					y = $('#y_coord').val();
			}
		});

		$('.btn_cancel').click(function(event){
			// $('#back_to_config').trigger('click');
			window.location = $('#back_to_config').attr('href');
		});

	});

	function loadAreaSelect() {
		var x = <?php echo $imageConfig['dashboard_img_x_offset']; ?>, 
			y = <?php echo $imageConfig['dashboard_img_y_offset']; ?>, 
			width = <?php echo $imageConfig['dashboard_img_width']; ?>, 
			height = <?php echo $imageConfig['dashboard_img_height']; ?>,
			orignal_width = <?php echo $imageConfig['orignal_width']; ?>,
			orignal_height = <?php echo $imageConfig['orignal_height']; ?>;

		var scale_x_down = width / orignal_width,
			scale_x_up = orignal_width / width;
			// scale_y_down = height / orignal_height,
			// scale_y_up = orignal_height / height,

		$('#dashboard_image').imgAreaSelect({
	        minWidth: Math.round(width*scale_x_down),
	        minHeight: Math.round(height*scale_x_down),
	        maxWidth: Math.round(width*scale_x_down),
	        maxHeight: Math.round(height*scale_x_down),
	        x1: Math.round(x*scale_x_down),
	        y1: Math.round(y*scale_x_down),
	        x2: Math.round(x*scale_x_down) + (width*scale_x_down),
	        y2: Math.round(y*scale_x_down) + (height*scale_x_down),
	        instance: true,
	        // handles: true,
	        // fadeSpeed: 400, 
	        onSelectEnd: function(image, selection)
	        {
	        	$('#x_coord').val(Math.round(selection.x1*scale_x_up));
	        	$('#y_coord').val(Math.round(selection.y1*scale_x_up));

	        	// console.info('x1:'+selection.x1+' y1:'+selection.y1+ ' x2:'+selection.x2+ ' y2:'+selection.y2);
	        }
	    });
	} 
</script>
