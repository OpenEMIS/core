<?php if ($action == 'view') : ?>

<?php else : ?>
	<?php
		$selectedColor  = 'ff00ff';
		echo $this->Html->css('/Quality/css/colorpicker', 'stylesheet', array('inline' => false));
		echo $this->Html->script('/Quality/js/colorpicker', false);
	?>

	<div class="form-group">
		<label class="col-md-3 control-label"><?php echo $this->Label->get('RubricTemplateOption.color');?></label>
		<div class="col-md-6">
			<input type="color" value="#ff0000" onchange="clickColor(0, -1, -1, 5)" class="form-control" id="html5colorpicker">
			<div id="colorSelector">
				<div style="background-color:#<?php echo $selectedColor; ?>"></div>
			</div>
			<?php echo $this->Form->hidden('color', array('id' => 'newColorPick')); ?>
		</div>
	</div>

	<script type="text/javascript">
		$('#colorSelector').ColorPicker({
			//flat: true
			color: '#<?php echo $selectedColor; ?>',
			onShow: function(colpkr) {
				$(colpkr).fadeIn(500);
				return false;
			},
			onHide: function(colpkr) {
				$(colpkr).fadeOut(500);
				return false;
			},
			onChange: function(hsb, hex, rgb) {
				$('#colorSelector div').css('backgroundColor', '#' + hex);
				$('#newColorPick').val(hex);
			}
		});
	</script>
<?php endif ?>
