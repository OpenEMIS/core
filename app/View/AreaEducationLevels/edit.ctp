<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('area', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('area', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper edit" style="min-height: 650px;">
	<h1>
		<span><?php echo __('Administrative Boundaries'); ?></span>
		<?php
		echo $this->Html->link(__('View'), array('action' => 'AreaEducationLevels'), array('class' => 'divider'));
		//echo $this->Html->link(__('Areas'), array('action' => 'index'), array('class' => 'divider', 'id' => 'viewAreas')); 
		?>
	</h1>
	<?php echo $this->element('area_categories'); ?>
	<?php echo $this->Form->create('AreaEducationLevels', array(
		'model' => 'AreaEducationLevel',
	    'inputDefaults' => array(
	        'label' => false,
	        'div' => false
	    )
	)); ?>

	<!-- <fieldset id="data_section_group" class="section_group edit" style="border: none;"> -->
		<!-- <legend>Administrative Boundaries: Area Levels</legend> -->
		<?php //$firstElement = array_shift($levels); ?>

		<div class="table full_width">
			<div class="table_head">
				<div class="table_cell " style="width:627px;"><?php echo __('Name'); ?></div>
				<!-- <div class="table_cell ">Name</div> -->
				<div class="table_cell" style="min-width: 20px;"> <?php echo __('Action'); ?> </div>
			</div><!-- 
			<div class="table_body">
	            <div class="table_row even">
					<input type="hidden" name="data[AreaLevel][0][id]" id="id" value="<?php echo $firstElement['id']; ?>">
					<input type="hidden" name="data[AreaLevel][0][level]" id="order" value="0">
		            <div class="table_cell cell_name_area_level">
						<div class="input_wrapper">
							<input type="text" name="data[AreaLevel][0][name]" value="<?php echo $firstElement['name']; ?>" id="">
						</div>
		            </div>
		            <div class="table_cell cell_order cell_order_area_level">
		            </div>
	            </div>
			</div> -->
		</div>
		
		<ul class="quicksand table_view">
		<?php for ($i=0; $i < count($levels); $i++) { ?>
			<li data-id="<?php echo $i+1; ?>">
				<input type="hidden" name="data[AreaEducationLevel][<?php echo $i; ?>][id]" id="id" value="<?php echo $levels[$i]['id']; ?>">
				<input type="hidden" name="data[AreaEducationLevel][<?php echo $i; ?>][level]" id="order" value="<?php echo $i+1; ?>">
				<div class="cell cell_name_area_level">
					<div class="input_wrapper">
						<input type="text" name="data[AreaEducationLevel][<?php echo $i; ?>][name]" value="<?php echo $levels[$i]['name']; ?>">
					</div>
				</div>
				<div class="cell cell_order_area_level"> 
<!--					<span class="icon_up" onclick="areas.reorder(this)"></span>
					<span class="icon_down" onclick="areas.reorder(this)"></span> -->
				</div>
			</li>
		<?php } ?>
		</ul>
		
		<?php if($_add) { ?>
		<div class="row"><a class="void icon_plus link_add_area_level" href="#"><?php echo __('Add') .' '. __('Area Level'); ?></a></div>
		<?php } ?>
		
		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" id="area_level_submit" onclick="$.mask({parent: '.edit', text: 'Saving'})"/>
			<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
		</div>
	<!-- </fieldset> -->
	
	<?php echo $this->Form->end(); ?>
</div>



<script type="text/javascript">
$(document).ready(function(){
	areas.extraParam = 'Education';
	var getKeys = function(obj){
	   var keys = [];
	   for(var key in obj){
	      keys.push(key);
	   }
	   return keys;
	}

	areas.isEditable = true;
	areas.extraParam = 'Education';
	jsList.init($('.table_view'));

	$('.btn_cancel').click(function(event){
		event.preventDefault();

		window.location = areas.baseURL;
	});
	
});

</script>
