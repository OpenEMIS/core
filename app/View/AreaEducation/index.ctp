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
			echo $this->Html->link(__('Edit'), array('action' => 'AreaEducationEdit'), array('class' => 'divider','id'=>'edit'));
		}
		/*if($_view_levels) {
			echo $this->Html->link(__('Area Levels'), array('action' => 'levels'), array('class' => 'divider')); 
		}*/
		?>
	</h1>
	<?php echo $this->element('area_categories'); ?>
	<?php
	echo $this->Form->create('AreaEducation', array(
			'url' => array(
				'controller' => 'Areas',
				'action' => 'AreaEducation'
			),
			'model' => 'AreaEducation',
            'inputDefaults' => array('label' => false, 'div' =>false)
		)
	);

	?>
	<fieldset id="area_section_group" class="section_group">
        <legend id="education"><?php echo __('Area'); ?></legend>
        <?php echo @$this->Utility->getAreaPicker($this->Form, 'area_education_id',$initAreaSelection['area_id'], array()); ?>
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
</div>



<script type="text/javascript">
$(document).ready(function () {
	$('#edit').click(function(event){
		event.preventDefault();
		var form = $('form').attr('action', getRootURL() + 'Areas/AreaEducationEdit');
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
	areas.extraParam = 'Education';
	areas.fetchData();
	<?php } ?>
	
});

</script>
	
	
