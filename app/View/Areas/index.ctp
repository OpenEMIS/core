<?php
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('area', 'stylesheet', array('inline' => false));

echo $this->Html->script('area', false);
?>

<?php echo $this->element('breadcrumb'); ?>

<div class="content_wrapper" style="min-height: 650px;">
	<h1>
		<span><?php echo __('Administrative Boundaries'); ?></span>
		<?php
		if($_edit) {
			echo $this->Html->link(__('Edit'), array('action' => 'index'), array('class' => 'divider', 'id'=>'edit'));
		}
		?>
	</h1>
	<?php echo $this->element('alert'); ?>
	<?php echo $this->element('area_categories'); ?>
    <?php if(count($topArea)>1){ ?>
	<?php
	echo $this->Form->create('Area', array(
			'url' => array(
				'controller' => 'Areas',
				'action' => 'index'
			),
			'model' => 'Area',
            'inputDefaults' => array('label' => false, 'div' =>false)
		)
	);
	?>
	<input id="selectedArea" type="hidden" value="1" name="selectedArea"></input>
	<fieldset id="area_section_group" class="section_group">
		<legend id="area"><?php echo __('Area'); ?></legend>
		<?php echo @$this->Utility->getAreaPicker($this->Form, 'area_id',$initAreaSelection['area_id'], array()); ?>
	</fieldset>
	<?php echo $this->Form->end(); ?>

	<fieldset id="data_section_group" class="section_group">
		<legend><?php echo __('Administrative Boundaries'); ?></legend>

		<div id="mainlist">
			<div class="table" style="table-layout: auto;">
				<div class="table_head">
						<div class="table_cell" style="width:20px;"><?php echo __('Visible'); ?></div>
						<div class="table_cell" style="width:100px;"><?php echo __('Level'); ?></div>
						<div class="table_cell" style="width:100px;"><?php echo __('Code'); ?></div>
						<div class="table_cell"><?php echo __('Name'); ?></div>
				</div>
				<div class="table_body" style="display:none;">
				</div>
			</div>
		</div>
	</fieldset>
	<script type="text/javascript">
    $(document).ready(function () {
    	$('#edit').click(function(event){
    		event.preventDefault();
    		var form = $('form').attr('action', getRootURL() + 'Areas/edit');
    		$('form').submit();
    	});

    	<?php if(isset($initAreaSelection) && count($initAreaSelection) > 0){ ?>
    	areas.initAreaSelection = <?php echo json_encode($initAreaSelection); ?>;
    	var currentSelect;
    	for(var key in areas.initAreaSelection){
    		currentSelect = $('select[name*="['+key+']"]');
    		areas.parentAreaIds.push(parseInt(areas.initAreaSelection[key]));
    		currentSelect.find($('option[value="'+areas.initAreaSelection[key]+'"]')).attr('selected','selected');
    		//$('select[name*="['+key+']"]').find($('option[value="'+areas.initAreaSelection[key]+'"]')).attr('selected','selected').trigger('change');
    	}

    	currentSelect.find($('option[value="'+areas.initAreaSelection[key]+'"]')).trigger('change');
    	<?php }else{?>
    	    areas.fetchData();
    	<?php } ?>
    });
    </script>
    <?php } ?>
</div>





	
	
