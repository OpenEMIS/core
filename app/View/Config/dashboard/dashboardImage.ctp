<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('configuration', 'stylesheet', array('inline' => false));
echo $this->Html->css('imgareaselect-default.css', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.imgareaselect.pack.js', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('System Configurations'));

$this->start('contentActions');
echo $this->Html->link(__('Back to List'), array('controller' => 'Config', 'action' => 'dashboard'), array('class' => 'divider', 'id' => 'back_to_config'));
$this->end();

$this->start('contentBody'); ?>
<?php echo $this->element('alert'); ?>
?>

<div id="site" class="content_wrapper dashboard_wrapper">
	<h1>
		<span><?php echo __('Dashboard Image'); ?></span>
		<?php echo $this->Html->link(__('Back to List'), array('controller' => 'Config', 'action' => 'dashboard'), array('class' => 'divider', 'id' => 'back_to_config')); ?>
		<!-- <a class="void link-edit divider">Edit</a> -->
	</h1>
	<?php if(isset($data)){ ?>
	<?php echo $this->element('alert'); ?>
	<!-- <fieldset class="section_break">
		<legend>Dashboard</legend>
	</fieldset> -->
	
	<div id="loader" class="loading">
		<?php //echo $this->Html->image($imageConfig['image_orignal'], array('alt' => 'Dashboard', 'width' => "{$imageConfig['width']}px", 'id'=>'dashboard_image'))?>
	</div>
	<br/><br/>
	<?php
	echo $this->Form->create('');
	?>

	<div class="controls">
		<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" />
		<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
	</div>
	<input type="hidden" name="data[ConfigItem][id]" id="image_id" value='<?php echo $data["id"]; ?>' />
	<input type="hidden" name="data[ConfigItem][x]" id="x_coord" value='<?php echo $data['x']; ?>' />
	<input type="hidden" name="data[ConfigItem][y]" id="y_coord" value='<?php echo $data['y']; ?>' />
	<input type="hidden" name="data[ConfigItem][action]" id="action" value='edit' />
	<?php echo $this->Form->end(); ?>
	<?php }else{ ?>
		<div><strong>No Image</strong></div>
	<?php } ?>
</div>

<?php //echo $this->element('sql_dump'); ?>
<?php if(isset($data)) { ?>
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
	    .attr('src', '<?php echo $this->Html->url(array("controller" => "Config", "action" => "fetchImage", $data["id"])); ?>')
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
		var x = <?php echo $data['x']; ?>, 
			y = <?php echo $data['y']; ?>, 
			width = <?php echo $imageConfig['dashboard_img_width']; ?>, 
			height = <?php echo $imageConfig['dashboard_img_height']; ?>,
			orignal_width = <?php echo $data['width']; ?>,
			orignal_height = <?php echo $data['height']; ?>,
			scale_down =  width/orignal_width,
			scale_up =  orignal_width / width;
			// scale_y = height / orignal_height,
			// scale = scale_x;

			
			// console.info(x);
			// console.info(y);
			// console.info(width);
			// console.info(height);
			// console.info(orignal_width);
			// console.info(orignal_height);
			// console.info('scale_down: ' + scale_down);
			// console.info('scale_up: ' + scale_up);
			// console.info('scale: ' + scale);
			// console.info('scale_y_down: ' + scale);
			// console.info('scale_y_up: ' + scale);
			// console.info('width: '+Math.round(width*scale_down));
			// console.info('height: '+Math.round(height*scale_down));
			// console.info('x1: ' + Math.round(x*scale_down));
			// console.info('y1: ' + Math.round(y*scale_down));
			// console.info('x1: ' + (Math.round((x*scale_down) + (orignal_width*scale_down))));
			// console.info('y2: ' + (Math.round((y*scale_down) + (orignal_height*scale_down))));

		$('#dashboard_image').imgAreaSelect({
	        minWidth: Math.round(width*scale_down),
	        minHeight: Math.round(height*scale_down),
	        maxWidth: Math.round(width*scale_down),
	        maxHeight: Math.round(height*scale_down),
	        x1: Math.round(x*scale_down),
	        y1: Math.round(y*scale_down),
	        x2: Math.round((x*scale_down) + (width*scale_down)),
	        y2: Math.round((y*scale_down) + (height*scale_down)),
	        instance: true,
	        // handles: true,
	        // fadeSpeed: 400, 
	        onSelectEnd: function(image, selection)
	        {
	        	// $('#x_coord').val(Math.round(selection.x1*scale_x_up));
	        	$('#x_coord').val(Math.round(selection.x1*scale_up));
	        	// $('#y_coord').val(Math.round(selection.y1*scale_y_up));
	        	$('#y_coord').val(Math.round(selection.y1*scale_up));

	        	// console.info('x1:'+selection.x1+' y1:'+selection.y1+ ' x2:'+selection.x2+ ' y2:'+selection.y2);
	        }
	    });
	} 
</script>
<?php } ?>
