<?php
echo $this->Html->css('table.old', 'stylesheet', array('inline' => false));
echo $this->Html->css('population', 'stylesheet', array('inline' => false));

echo $this->Html->script('population', false);

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __('Population'));
$this->start('contentActions');
if($_edit) { 
	echo $this->Html->link(__('Edit'), array('action' => 'edit'), array('id' => 'edit', 'class' => 'divider'));
}
$this->end();
$this->assign('contentId', 'population');
$this->start('contentBody');

$currentYear = intval(date('Y'));
$selectedYear = (isset($selectedYear))? $selectedYear : $currentYear;
?>
<?php echo $this->element('alert'); ?>

<?php
echo $this->Form->create('Population', array(
		'url' => array(
			'controller' => 'Population',
			'action' => 'index'
		),
		'model' => 'Population',
        'inputDefaults' => array('label' => false, 'div' =>false)
	)
);
?>

<div class="row year">
	<div class="label"><?php echo __('Year'); ?></div>
	<?php 
		echo $this->Utility->getYearList($this->Form,'data[year]',array(
			'name' => "data[year]",
			'id' => "year_id",
			'class'=>'form-control',
			'div' => 'col-md-4',
			'maxlength' => 30,
			'desc' => true,
			'label' => false,
			'default' => $selectedYear,
			'div' => false), true);
	?>
            <?php echo $this->element('census_legend_population'); ?>
</div>

<fieldset id="area_section_group" class="section_group">
    <legend id="area"><?php echo __('Area'); ?></legend>
    <?php echo @$this->Utility->getAreaPicker($this->Form, 'area_id', $initAreaSelection['area_id'], array()); ?>
</fieldset>

<?php echo $this->Form->end(); ?>

<fieldset id="data_section_group" class="section_group">
	<legend><?php echo __('Population'); ?></legend>
	<div id="mainlist">
		<div class="table">
			<div class="table_head">
				<!--div class="table_cell">Area Level</div-->
				<div class="table_cell cell_source"><?php echo __('Source'); ?></span></div>
				<div class="table_cell"><?php echo __('Age'); ?></div>
				<div class="table_cell"><?php echo __('Male'); ?></div>
				<div class="table_cell"><?php echo __('Female'); ?></div>
				<div class="table_cell"><?php echo __('Total'); ?></div>
			</div>

			<div class="table_body" style="display:none;">&nbsp;</div>

			<div class="table_foot">
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell"></div>
				<div class="table_cell cell_label"><?php echo __('Total'); ?></div>
				<div class="table_cell cell_value cell_number">0</div>
			</div>
		</div>
	</div>
</fieldset>

<script type="text/javascript">

$(document).ready(function(){
	var selectedYear = <?php echo $selectedYear?>;
	
	if($('#year_id').children().first().val().length < 1){
		$('#year_id').children().first().remove();
	}

	if(selectedYear !== '' || selectedYear !== undefined || selectedYear !== 'null'){
		//$("#year_id option[value='"+ selectedYear +"']").attr('selected','selected').trigger('change');
		population.year = $("#year_id option[value='"+ selectedYear +"']").attr('selected','selected').val();

	}else{
		//$("#year_id option[value='"+ new Date().getFullYear() +"']").attr('selected','selected').trigger('change');
		population.year = $("#year_id option[value='"+ new Date().getFullYear() +"']").attr('selected','selected').val();

	}
	$('#edit').click(function(event){
		event.preventDefault();
		var form = $('form').attr('action', getRootURL() + 'Population/edit');
		$('form').submit();
	});
	
	<?php if(isset($initAreaSelection) && count($initAreaSelection) > 0){ ?>
	population.initAreaSelection = <?php echo json_encode($initAreaSelection); ?>;
	var currentSelect;

	for(var key in population.initAreaSelection){
		currentSelect = $('select[name*="['+key+']"]');
		population.parentAreaIds.push(parseInt(population.initAreaSelection[key]));
		currentSelect.find($('option[value="'+population.initAreaSelection[key]+'"]')).attr('selected','selected');
		// $('select[name*="['+key+']"]').find($('option[value="'+population.initAreaSelection[key]+'"]')).attr('selected','selected').trigger('change');

	}
	currentSelect.find($('option[value="'+population.initAreaSelection[key]+'"]')).trigger('change');

	<?php } ?>
});


</script>
<?php $this->end(); ?>
