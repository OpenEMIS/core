<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('area', 'stylesheet', array('inline' => false));

echo $this->Html->script('jquery.quicksand', false);
echo $this->Html->script('jquery.sort', false);
echo $this->Html->script('area', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper" style="min-height: 650px;">
	<h1>
		<span><?php echo __('Administrative Boundaries'); ?></span>
		<?php 
			echo $this->Html->link(__('View'), array('action' => 'AreaEducation'), array('class' => 'divider','id'=>'view')); 
			
			// if($_view_levels) {
			//echo $this->Html->link(__('Area Levels'), array('action' => 'levels'), array('class' => 'divider')); 
			// }
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	<?php echo $this->element('area_categories'); ?>
	<?php
	echo $this->Form->create('AreaEducation', array(
			'url' => array(
				'controller' => 'Areas',
				'action' => 'edit'
			),
			'model' => 'AreaEducation',
            'inputDefaults' => array('label' => false, 'div' =>false)
		)
	);

	?>

    <?php if(isset($initAreaSelection['area_education_id'])){ ?>
	<fieldset id="area_section_group" class="section_group">
        <legend id="education"><?php echo __('Area'); ?></legend>
        <?php echo @$this->Utility->getAreaPicker($this->Form, 'area_education_id',$areaId, array()); ?>
    </fieldset>
    <?php } ?>

	<?php echo $this->Form->end(); ?>

	<fieldset id="data_section_group" class="section_group edit">
		<legend><?php echo __('Administrative Boundaries'); ?></legend>
            <div class="table">
                <div class="table_head">
                    <div class="table_cell" style="min-width:38px;"><?php echo __('Visible'); ?></div>
                    <div class="table_cell" style="width:99px;"><?php echo __('Level'); ?></div>
                    <div class="table_cell" style="width:90px;"><?php echo __('Code'); ?></div>
                    <div class="table_cell" style="width:280px;"><?php echo __('Name'); ?></div>
                    <?php if($_add && isset($initAreaSelection['area_education_id'])) { ?>
                    <div class="table_cell"><?php echo __('Order'); ?></div>
                    <?php } ?>
                </div>
                <div class="table_body" style="display:none;">
                </div>
            </div>

            <ul class="quicksand table_view" style="margin-bottom:12px;"></ul>

            <?php if($_add && isset($initAreaSelection['area_education_id'])) { ?>
                <div id="addarea" class="row"><a class="void icon_plus link_add"><?php echo __('Add') .' '. __('Area'); ?></a></div>
            <?php } ?>

		<div class="controls">
			<input type="submit" value="<?php echo __('Save'); ?>" class="btn_save btn_right" onClick="areas.save()" />
			<input type="button" value="<?php echo __('Cancel'); ?>" class="btn_cancel btn_left" />
		</div>
	</fieldset>
</div>



<script type="text/javascript">
$(document).ready(function(){

	var getKeys = function(obj){
	   var keys = [];
	   for(var key in obj){
	      keys.push(key);
	   }
	   return keys;
	}

	areas.isEditable = true;
	$('#view, .btn_cancel').click(function(event){
		event.preventDefault();
		var form = $('form').attr('action', getRootURL() +'Areas/AreaEducation/');
		form.submit();
	});


	<?php if(isset($initAreaSelection) && count($initAreaSelection) > 0){ ?>
	areas.initAreaSelection = <?php echo json_encode($initAreaSelection); ?>;
	// var totalAreaSelector = $('select[name*="area_level_"]').length;
	// var initAreaSelectionKeys = getKeys(areas.initAreaSelection);
	// if(initAreaSelectionKeys.length > totalAreaSelector){
	// 	delete areas.initAreaSelection[initAreaSelectionKeys.pop()];
	// }

	var currentSelect;
	for(var key in areas.initAreaSelection){
		currentSelect = $('select[name*="['+key+']"]');
		areas.parentAreaIds.push(parseInt(areas.initAreaSelection[key]));
		currentSelect.find($('option[value="'+areas.initAreaSelection[key]+'"]')).attr('selected','selected');
		//$('select[name*="['+key+']"]').find($('option[value="'+areas.initAreaSelection[key]+'"]')).attr('selected','selected').trigger('change');
	}

	currentSelect.find($('option[value="'+areas.initAreaSelection[key]+'"]')).trigger('change');
	<?php }else{ ?>
	areas.extraParam = 'Education';
	areas.fetchData();
	<?php } ?>



});

</script>

